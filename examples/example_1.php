<?php
/**
 * @file examples/example_1.php
 *
 * This example shows how to use an event listener to change the root
 * supplied to the crawl method
 *
 * In this example we will pass the domain `www.example.com` to Spider::crawl
 * but will change it in the `Spider::SPIDER_CRAWL_PRE` event.
 *
 * We also attach to the `Spider::SPIDER_CRAWL_PAGE_PRE` event to show that the
 * root has been changed
 */
use PhpSpider\Spider\Spider;
use Zend\EventManager\Event;

chdir(__DIR__);

require './..//vendor/autoload.php';

$preListener = function (Event $event) {
    $root = $event->getParam('root');
    echo "The root uri was $root" . PHP_EOL;
    // changing the root
    $event->setParam('root', 'http://ftp.example.com');
};

$pagePreListener = function (Event $event) {
    $uri = $event->getParam('uri');
    echo "About to crawl {$uri->toString()}" . PHP_EOL;
};

$spider = new Spider();
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_PRE, $preListener, 1);
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_PAGE_PRE, $pagePreListener, 1);

$spider->crawl('https://www.example.com');