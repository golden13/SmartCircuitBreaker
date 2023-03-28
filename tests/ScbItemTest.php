<?php
declare(strict_types=1);

include_once __DIR__ . "/../src/Scb.php";

use PHPUnit\Framework\TestCase;
use Golden13\Scb\Scb;

class ScbItemTest extends TestCase
{
    protected function getCustomConfig(): array {
        $randomPrefix = rand(1,100) . '_' . microtime(true);

        return [
            'randomPrefix' => $randomPrefix,
            'dummy-filed' => true,
            'enabled' => true, // if circuit breaker logic is enabled
            'defaultLogLevel' => 'debug',

            'items' => [
                // The * corresponds to any item, excluding specified as a separate item
                '*' => [
                    'numberOfErrorsToOpen' => 3, // Threshold to open circuit breaker
                    'ttlForFail' => 15, // after this amount of seconds, the status will be invalidated during the script init stage.
                    'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20], // List of timeouts in seconds
                    'storage' => 'file', // file | redis | memcache
                ],
                'memcache' => [
                    'numberOfErrorsToOpen' => 4, // Threshold to open circuit breaker
                    'ttlForFail' => 15, // after this amount of seconds, the status will be invalidated during the script init stage.
                    'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20], // List of timeouts in seconds
                    'storage' => 'memcache', // file | redis | memcache
                ],
            ],
            // Storages
            'storages' => [
                'file' => [
                    'prefix' => 'scbtest_' . $randomPrefix . '_',
                    'path' => '/tmp', // no trailing slash
                ],
                'redis' => [
                    'prefix' => 'scbtest_' . $randomPrefix . '_',
                ],
                'memcache' => [
                    'prefix' => 'scbtest_' . $randomPrefix . '_',
                    'host' => 'localhost',
                    'port' => '11211',
                    'igbinaryEnabled' => false,
                ],
            ],
        ];
    }

    public function testExecute(): void {
        Scb::reset();
        $instance = Scb::getInstance();
        // load custom config
        $instance->loadConfig($this->getCustomConfig());

        $testItem = $instance->item("test1");

        // Fail 1
        try {
            $testItem->execute(function () {
                throw new \Exception("Exception");
            });
        } catch (\Exception $e) {

        }
        // Scb should be closed
        $this->assertTrue($testItem->isClosed());

        // Fail 2
        try {
            $testItem->execute(function () {
                throw new \Exception("Exception");
            });
        } catch (\Exception $e) {

        }

        // Scb should be closed
        $this->assertTrue($testItem->isClosed());


        // Fail 3
        try {
            $testItem->execute(function () {
                throw new \Exception("Exception");
            });
        } catch (\Exception $e) {

        }

        // Scb should be closed
        $this->assertTrue($testItem->isClosed());

        // Fail 4
        try {
            $testItem->execute(function () {
                throw new \Exception("Exception");
            });
        } catch (\Exception $e) {

        }

        // Scb should be open
        $this->assertFalse($testItem->isClosed());
        $this->assertEquals($testItem->getTotalFailedCalls(), 4);

        // Let's sleep for 16 seconds, to validate
        sleep(16); // after 15 seconds the CB should close automatically

        $this->assertTrue($testItem->isClosed());

        $statusFromFileSystem = $testItem->getStorage()->get(); // will return status from the file system

        // Destroying item. The destructor should be called, and the Status file should be updated
        $testItem = null;

        // reinitialize the same item
        $testItem = $instance->item("test1");
        $newStatus = $testItem->readStatus();

        $this->assertNotEquals($statusFromFileSystem, $newStatus);

        // TODO: clean files after tests
    }
}
