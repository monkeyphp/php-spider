<?php
/**
 * SpiderTest.php
 */
namespace PhpSpiderTest\Spider;

use PhpSpider\Spider\Spider;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use stdClass;

/**
 * SpiderTest
 */
class SpiderTest extends PHPUnit_Framework_TestCase
{
    /**
     * PHP web server
     *
     * @var resource|null
     */
    protected $webserver;

    /**
     * Set up the local web server
     *
     * ```bash
     *     ps -ef | grep php
     * ```
     *
     * @link http://stackoverflow.com/questions/30267329/how-do-i-run-phps-built-in-web-server-in-the-background
     * @link https://coderwall.com/p/5pv1rw/start-php5-4-built-in-server-in-background
     */
    protected function startWebserver()
    {
        $webserver = $this->webserver;

        if (! is_resource($webserver)) {
            $cmd = 'exec php -S localhost:8000 -t ./tests/html/ > ./tests/log 2>&1 &';
            $descriptorspec = [];
            $cwd = getcwd();
            $env = [];

            $webserver = proc_open($cmd, $descriptorspec, $pipes, $cwd, $env);
            sleep(1);

            if (! is_resource($webserver)) {
                throw new RuntimeException('Unable to start the webserver');
            }

            $this->webserver = $webserver;
        }
    }

    /**
     * Stop the webserver
     *
     * @todo The +1 looks like a hack
     *
     * @link http://php.net/manual/en/function.proc-terminate.php
     * @link http://nl1.php.net/manual/en/function.proc-get-status.php
     *
     * @return null
     */
    protected function stopWebserver()
    {
        $webserver = $this->webserver;

        if (is_resource($webserver)) {
            $status = proc_get_status($webserver);
            $ppid = $status['pid'];
            posix_kill($ppid + 1, 9);
        }
    }

    /**
     * Set up
     *
     * @return null
     */
    public function setUp()
    {
        $this->startWebserver();
    }

    /**
     * Tear down
     *
     * @return null
     */
    public function tearDown()
    {
        $this->stopWebserver();
    }

    public function testConstruct()
    {
        $spider = new Spider();

        $this->assertInstanceOf('PhpSpider\Spider\Spider', $spider);
    }

    public function testCrawl()
    {
        $spider = new Spider();
        $spider->crawl('http://localhost:8000');
    }

    public function testCrawlWithNoRoot()
    {
        $spider = new Spider();

        $spider->crawl(null);
    }

    public function testCrawlWithInvalidObject()
    {
        $spider = new Spider();

        $spider->crawl(new stdClass());
    }

    public function testCrawlWithRelativeUrl()
    {
        $spider = new Spider();

        $spider->crawl('http://this/is/my/page');
    }
}
