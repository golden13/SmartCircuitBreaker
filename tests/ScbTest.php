<?php
declare(strict_types=1);

include_once __DIR__ . "/../src/Scb.php";

use PHPUnit\Framework\TestCase;
use Golden13\Scb\Scb;

final class ScbTest extends TestCase
{
    protected function getCustomConfig(): array {
        return [
            'dummy-filed' => true,
            'enabled' => true, // if circuit breaker logic is enabled
            'defaultLogLevel' => 'debug',

            'items' => [
                // The * corresponds to any item, excluding specified as a separate item
                '*' => [
                    'numberOfErrorsToOpen' => 5, // Threshold to open circuit breaker
                    'ttlForFail' => 60, // after this amount of seconds, the status will be invalidated during the script init stage.
                    'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20,30,45,60], // List of timeouts in seconds
                    'storage' => 'file', // file | redis | memcache
                ],
                'memcache' => [
                    'numberOfErrorsToOpen' => 4, // Threshold to open circuit breaker
                    'ttlForFail' => 60, // after this amount of seconds, the status will be invalidated during the script init stage.
                    'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20,30,45,60], // List of timeouts in seconds
                    'storage' => 'memcache', // file | redis | memcache
                ],
            ],
            // Storages
            'storages' => [
                'file' => [
                    'prefix' => 'scbtest_',
                    'path' => '/tmp', // no trailing slash
                ],
                'redis' => [
                    'prefix' => 'scbtest_',
                ],
                'memcache' => [
                    'prefix' => 'scbtest_',
                    'host' => 'localhost',
                    'port' => '11211',
                    'igbinaryEnabled' => false,
                ],
            ],
        ];
    }

    public function testGetInstance(): void {
        Scb::reset();
        $instance1 = Scb::getInstance();
        $instance2 = Scb::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testLoadConfigDefault(): void {
        Scb::reset();
        Scb::getInstance()->loadConfig();
        $conf = include dirname(__FILE__) . "/../src/config.default.php"; // default config
        $this->assertSame(Scb::getInstance()->getConfig(), $conf);
    }

    public function testLoadConfigFromFile(): void {
        Scb::reset();
        $fileName = "src/config.default.php";
        Scb::getInstance()->loadConfig($fileName);
        $conf = include "src/config.default.php"; // default config
        $this->assertSame(Scb::getInstance()->getConfig(), $conf);
    }

    public function testLoadConfigCustom(): void {
        Scb::reset();
        $customConfig = $this->getCustomConfig();
        Scb::getInstance()->loadConfig($customConfig);

        $this->assertSame(Scb::getInstance()->getConfig(), $customConfig);
    }

    public function testItem(): void {
        Scb::reset();
        $customConfig = [
            'numberOfErrorsToOpen' => 222,
            'ttlForFail' => 10,
            'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20,30,45,60],
            'storage' => 'file', // file | redis | memcache
        ];

        Scb::getInstance()->item("test1", null, $customConfig);

        $this->assertEquals("test1", Scb::getInstance()->item("test1")->getName());
        $this->assertEquals($customConfig['ttlForFail'], Scb::getInstance()->item("test1")->getTtlForFail());
    }

    public function testBuildStorageByType(): void {
        Scb::reset();
        $typeFile1 = Scb::STORAGE_FILE;

        $storage1 = Scb::getInstance()->buildStorageByType($typeFile1);
        $this->assertIsObject($storage1);
        $this->assertNotEmpty($storage1);
        $this->assertInstanceOf(\Golden13\Scb\FileStorage::class, $storage1);

        $typeFile2 = Scb::STORAGE_MEMCACHE;
        $storage2 = Scb::getInstance()->buildStorageByType($typeFile2);
        $this->assertIsObject($storage2);
        $this->assertNotEmpty($storage2);
        $this->assertInstanceOf(\Golden13\Scb\MemcacheStorage::class, $storage2);

        /*
        $typeFile3 = Scb::STORAGE_REDIS;
        $storage3 = Scb::getInstance()->buildStorageByType($typeFile3);
        $this->assertIsObject($storage3);
        $this->assertNotEmpty($storage3);
        $this->assertInstanceOf("RedisStorage", $storage3);
        */

        $typeFile4 = 'dummy';
        $storage4 = Scb::getInstance()->buildStorageByType($typeFile4);
        $this->assertEmpty($storage4);
        //$this->assertEmpty($storage4);
    }

    public function testDisabled(): void {
        Scb::reset();
        $customConfig = $this->getCustomConfig();
        // disable it
        $customConfig['enabled'] = false;
        Scb::getInstance()->loadConfig($customConfig);
        $item = Scb::getInstance()->item("test1");

        $this->assertFalse($item->isEnabled());
        $this->assertFalse(Scb::getInstance()->isEnabled());
    }
}
