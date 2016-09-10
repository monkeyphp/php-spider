# PhpSpider

---

> I needed a really basic spider to crawl a site to warm the cache; this is the result.


## Examples

### Simple 

The simplest way to use PhpSpider is to construct an instance and pass the root
url of the site that you wish to crawl to the `Spider::crawl` method.

By design, PhpSpider will only crawl pages that 

- Return a `content-type` header of `text\html`
- That are on the same domain as the supplied root 

```php
use PhpSpider\Spider\Spider;

$spider = new Spider();
$spider->crawl('https://www.example.com');
```

### Advanced

If you need to override how PhpSpider works you can add event listeners to be
notified, that can then affect how PhpSpider operates.

There are 5 events triggered by PhpSpider 

- **Spider::SPIDER_CRAWL_PRE** - Triggered before PhpSpider starts to crawl a site.
- **Spider::SPIDER_CRAWL_POST** - Triggered one PhpSpider has finished it's crawl.
- **Spider::SPIDER_CRAWL_PAGE_PRE** - Triggered just before a page is crawled
- **Spider::SPIDER_CRAWL_PAGE_POST** - Triggered once a page is crawled
- **Spider::SPIDER_CRAWL_PAGE_ERROR** - Trigged if an error occurs whilst crawling a page


You can find examples in the examples directory included in this repository.

```bash
    $ php ./examples/example_0.php
```


```php
use PhpSpider\Spider\Spider;
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
