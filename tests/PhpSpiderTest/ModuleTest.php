<?php
/**
 * ModuleTest.php
 */
namespace PhpSpiderTest;

use PhpSpider\Module;
use PHPUnit_Framework_TestCase;

/**
 * ModuleTest
 */
class ModuleTest extends PHPUnit_Framework_TestCase
{
    public function testConstructInstance()
    {
        $module = new Module();

        $this->assertInstanceOf('PhpSpider\Module', $module);
    }

    public function testGetConfigReturnsArray()
    {
        $module = new Module();

        $this->assertInternalType('array', $module->getConfig());
    }

    public function testGetAutoloaderConfigReturnsArray()
    {
        $module = new Module();

        $this->assertInternalType('array', $module->getAutoloaderConfig());
    }
}
