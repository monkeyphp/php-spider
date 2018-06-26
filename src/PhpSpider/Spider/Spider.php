<?php
/**
 * Spider.php
 */
namespace PhpSpider\Spider;

use Exception;
use Iterator;
use Zend\Dom\Document;
use Zend\Dom\Document\Query;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\EventManager\ResponseCollection;
use Zend\Http\Client;
use Zend\Http\Header\ContentType;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\SplQueue;
use Zend\Uri\Http;
use Zend\Uri\Uri;

/**
 * Spider
 */
final class Spider implements EventManagerAwareInterface
{
    /**
     * Traits
     */
    use EventManagerAwareTrait;

    /**
     * Events
     */
    const SPIDER_CRAWL_PRE  = 'spider_crawl.pre';
    const SPIDER_CRAWL_POST = 'spider_crawl.post';

    const SPIDER_CRAWL_PAGE_PRE   = 'spider_crawl_page.pre';
    const SPIDER_CRAWL_PAGE_POST  = 'spider_crawl_page.post';
    const SPIDER_CRAWL_PAGE_ERROR = 'spider_crawl_page.error';

    /**
     * Queue
     *
     * @var SplQueue|null
     */
    protected $queue;

    /**
     * Instance of Client
     *
     * @var Client|null
     */
    protected $client;

    /**
     * The root uri to start the crawl from
     *
     * @var string|null
     */
    protected $root;

    /**
     * Options to use when creating an instance
     * of the client.
     * @var array|null
     */
    protected $clientOptions;

    /**
     * Default options used when creating an instance
     * of the client.
     * @var array|null
     */
    protected $defaultOptions = [
        'useragent'    => 'PhpSpider',
        'timeout'      => 30,
        'maxredirects' => 1,
    ];

    /**
     * Construct an instance of PhpSpider\Spider with the passed in options.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->clientOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * Return the Client instance
     *
     * @return Client
     */
    protected function getClient()
    {
        if (! isset($this->client)) {
            $client = new Client();
            $client->setOptions($this->clientOptions);
            $this->setClient($client);
        }
        return $this->client;
    }

    /**
     * Set the Client instance
     *
     * @param Client|null $client Instance of Client
     *
     * @return SpiderInterface
     */
    protected function setClient(Client $client = null)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Return the root uri
     *
     * @return string|null
     */
    protected function getRoot()
    {
        return $this->root;
    }

    /**
     * Set the roots to start the crawl from
     *
     * @param string|Uri $root The root to start the crawl
     *
     * @return SpiderInterface
     */
    protected function setRoot($root = null)
    {
        // removed 26-June-2018 some domains return 404 if we add '/'
        // if ($root) {
        //     if (is_string($root) && ! (substr($root, -1) === '/')) {
        //         $root .= '/';
        //     }
        // }
        $this->root = $this->filterUri($root);
        return $this;
    }

    /**
     * Accept any old piece of shit variable and attempt to
     * construct a valid Http instance from it
     *
     * @param Uri|string $uri The Uri or string
     *
     * @return Http|null
     */
    protected function filterUri($uri = null)
    {
        if ($uri instanceof Http) {
            try {
                $uri = $uri->toString();
            } catch (Exception $exception) {
                $uri = null;
            }
        }

        if (! is_string($uri)) {
            return null;
        }

        try {
            $http = new Http($uri);
        } catch (Exception $exception) {
            return null;
        }

        if ($http->isValidRelative()) {
            if (null !== ($root = $this->getRoot())) {
                $http = new Http($root);
                $http->setPath($uri);
            }
        }

        if ($http->isValid() && $http->isAbsolute()) {
            // if we have a root then we need to check that it is a
            // uri on the same domain
            if (null !== ($root = $this->getRoot())) {
                if ($root->getHost() === $http->getHost()) {
                    $http = $http->normalize();
                } else {
                    $http = null;
                }
            // else we do not have a root
            } else {
                $http = $http->normalize();
            }
        } else {
            $http = null;
        }

        return $http;
    }

    /**
     * Return the body content from the supplied Response
     *
     * @param Response $response
     *
     * @return mixed|string|null
     */
    protected function filterResponse(Response $response)
    {
        $headers = $response->getHeaders();
        $contentType = $headers->get('Content-Type');

        if ($contentType instanceof ContentType) {
            $mediaType = $contentType->getMediaType();
            if ($mediaType === 'text/html') {
                $response = $response->getBody();
                return $response;
            }
        }

        return null;
    }

    /**
     * Return the Queue instance
     *
     * @return SplQueue
     */
    protected function getQueue()
    {
        if (! isset($this->queue)) {
            $queue = new SplQueue();
            $this->setQueue($queue);
        }
        return $this->queue;
    }

    /**
     * Set the Queue instance
     *
     * @param SplQueue|null $queue Set the queue instance
     *
     * @return SpiderInterface
     */
    protected function setQueue(SplQueue $queue = null)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Attach the default listeners
     *
     * Here we attach the default listeners that will provide the core/basic
     * implementation of the Spider.
     * The listeners are added with a low priority so that Spider uses can
     * override this implementation with their own.
     * 
     * @return null
     */
    protected function attachDefaultListeners()
    {
        $eventManager = $this->getEventManager();

        $that = $this;

        $eventManager->attach(self::SPIDER_CRAWL_PRE,
            function ($event) use ($that) {
               return $that->spiderCrawlPre($event);
            },
            -1000
        );

        $eventManager->attach(self::SPIDER_CRAWL_PAGE_PRE,
            function ($event) use ($that) {
               return $that->spiderCrawlPagePre($event);
            },
            -1000
        );

        $eventManager->attach(self::SPIDER_CRAWL_PAGE_ERROR,
            function ($event) use ($that) {
               return $that->spiderCrawlPageError($event);
            },
            -1000
        );

        $eventManager->attach(self::SPIDER_CRAWL_PAGE_POST,
            function ($event) use ($that) {
               return $that->spiderCrawlPagePost($event);
            },
            -1000
        );

        $eventManager->attach(self::SPIDER_CRAWL_POST,
            function ($event) use ($that) {
               return $that->spiderCrawlPost($event);
            },
            -1000
        );
    }

    /**
     * Called before we start the crawl
     *
     * Here, we wil set the root uri and then add the root to the queue
     *
     * @param Event $event
     *
     * @return null
     */
    protected function spiderCrawlPre(Event $event)
    {
        $root = $event->getParam('root');
        $this->setRoot($root);

        $root = $this->getRoot();

        if ($root instanceof Http) {
            $queue = $this->getQueue();
            $queue->enqueue($root);
        }
    }

    /**
     * Called before we crawl a page
     *
     * @param Event $event
     *
     * @return null
     */
    protected function spiderCrawlPagePre(Event $event)
    {
        $uri = $event->getParam('uri');

        $client = $this->getClient();
        $request = new Request();
        $request->setUri($uri);

        try {
            $response = $client->send($request);
            return $response;
        } catch (Exception $exception) {
            return $exception;
        }
    }

    /**
     * Called if an error has occurred while crawling a uri
     *
     * @interal `$results` may be a Zend\EventManager\ResponseCollection
     *
     * @param Event $event
     *
     * @return null
     */
    protected function spiderCrawlPageError(Event $event)
    {
        $results = $event->getParam('results');
        if ($results instanceof ResponseCollection) {
            $results = $results->last();
        }
    }

    /**
     * Called once a page has been crawled
     *
     * Here, we will parse the page and attempt to extract the valid urls and
     * add them to the Queue
     *
     * @param Event $event
     *
     * @return null
     */
    protected function spiderCrawlPagePost(Event $event)
    {
        $results = $event->getParam('results');

        if ($results instanceof ResponseCollection) {
            $results = $results->last();
        }

        if ($results instanceof Response) {
            $results = $this->filterResponse($results);
        }

        if (! $results || ! is_string($results)) {
            return null;
        }

        $links = $this->parsePageForLinks($results);

        return $links;
    }

    protected function parsePageForLinks($page)
    {
        $document = new Document($page);
        $nodeList = Query::execute('a', $document, Query::TYPE_CSS);
        $nodeList = ArrayUtils::iteratorToArray($nodeList, false);

        array_walk($nodeList, function (&$node) {
            $node = $node->getAttribute('href');
        });

        $nodeList = array_unique($nodeList);
        $nodeList = array_filter($nodeList);

        return $nodeList;
    }

    /**
     *
     * @param Event $event
     *
     * @return null
     */
    protected function spiderCrawlPost(Event $event)
    {
        $results = $event->getParam('results');
    }

    /**
     * Crawl
     *
     * @param string|null $root The root to start the crawl from
     *
     * @return null
     */
    public function crawl($root = null)
    {
        $eventManager = $this->getEventManager();

        $eventManager->trigger(Spider::SPIDER_CRAWL_PRE, $this, [
            'root' => $root
        ]);

        static $seen = [];
        $queue = $this->getQueue();

        while (! $queue->isEmpty()) {
            $uri = $queue->dequeue();

            // here we expect a Response to be returned
            $results = $eventManager->trigger(
                Spider::SPIDER_CRAWL_PAGE_PRE,
                $this,
                [
                    'uri' => $uri
                ],
                function ($v) {
                    return $v instanceof Response;
                }
            );

            if ($results->stopped()) {
                $results = $results->last();
            }

            if (! $results instanceof Response) {
                $eventManager->trigger(
                    Spider::SPIDER_CRAWL_PAGE_ERROR,
                    $this,
                    [
                        'results' => $results,
                    ]
                );
                continue;
            }

            // here we expect an array of uris to follow to be returned
            $results = $eventManager->trigger(
                Spider::SPIDER_CRAWL_PAGE_POST,
                $this,
                [
                    'uri' => $uri,
                    'results' => $results,
                ],
                function ($v) {
                    return ($v instanceof Iterator || is_array($v));
                }
            );

            if ($results->stopped()) {
                $results = $results->last();
            }

            // if the list of uris is an Iterator
            if ($results instanceof Iterator) {
                $results = ArrayUtils::iteratorToArray($results, false);
            }

            if (is_array($results)) {
                // before adding a uri to the Queue we'll remove any urls
                // that we've already seen
                $results = array_diff($results, $seen);
                $seen = ArrayUtils::merge($seen, $results);

                foreach ($results as $uri) {
                    $uri = $this->filterUri($uri);

                    if ($uri instanceof Http) {
                        $queue->enqueue($uri);
                    } else {
                        continue;
                    }
                }
            }
        }

        $eventManager->trigger(
            Spider::SPIDER_CRAWL_POST,
            $this
        );
    }
}
