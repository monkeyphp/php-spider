# PhpSpider

~ I needed a really basic spider to crawl a site to warm the cache; this is the result.

## Simple usage

```php
$spider = new \PhpSpider\Spider\Spider();
$spider->crawl('https://www.example.com');
```

## Add An Event Listener

```php
use new \PhpSpider\Spider\Spider;
use Zend\EventManager\Event;

$listener function ($event) {
    $uri = $event->getParam('uri');
    echo $uri;
};

$spider = new Spider();
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_PAGE_PRE, $listener, 1000);

$spider->crawl('https://www.example.com');
```

## To Run Tests

```bash
$ vendor/bin/phpunit -c tests/phpunit.xml
```