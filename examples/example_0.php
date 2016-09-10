<?php
/**
 * @file examples/example_0.php
 *
 * This example shows how to your own event listeners to each of the
 * events that PhpSpider triggers
 */
use PhpSpider\Spider\Spider;
use Zend\EventManager\Event;

chdir(__DIR__);

require './..//vendor/autoload.php';

$preListener = function (Event $event) {
    $root = $event->getParam('root');
    echo 'STARTED' . PHP_EOL;
};

$prePageListener = function (Event $event) {
    $uri = $event->getParam('uri');
    echo 'PRE' . PHP_EOL;
};

$errorPageListener = function (Event $event) {
    $results = $event->getParam('results');
    echo 'ERROR' . PHP_EOL;
};

$postPageListener = function (Event $event) {
    $results = $event->getParam('results');
    echo 'POST' . PHP_EOL;
};

$postListener = function (Event $event) {
    echo 'FINISHED' . PHP_EOL;
};

$spider = new Spider();
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_PRE, $preListener, 10);
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_PAGE_PRE, $prePageListener, 10);
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_PAGE_ERROR, $errorPageListener, 10);
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_PAGE_POST, $postPageListener, 10);
$spider->getEventManager()->attach(Spider::SPIDER_CRAWL_POST, $postListener, 10);

$spider->crawl('https://www.example.com');