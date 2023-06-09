<?php
include "../src/Scb.php";

use Golden13\Scb\Scb,Golden13\Scb\LogWrapper;

// test code
$smartCircuitBreaker = Scb::getInstance();

// Simple echo Logger
// You can use any PSR-3 Logger
$logger = new LogWrapper();
$smartCircuitBreaker->setLogger($logger);

// Run code with the exception in a loop
for ($i = 0; $i < 10; $i++) {
    try {
        $smartCircuitBreaker->item("dummy")->execute(function () {
            throw new \Exception("Some exception");
        });
    } catch (\Exception $e) {
        $logger->debug("Exception in simpleExample.php  i={$i}");
    }
}

// memcache test
for ($i = 0; $i < 10; $i++) {
    try {
        // memcache-test item is specified in config.default.php
        $smartCircuitBreaker->item("memcache-test")->execute(function () {
            throw new \Exception("Some exception");
        });
    } catch (\Exception $e) {
        $logger->debug("Memcache-test, exception in simpleExample.php  i={$i}");
    }
}


// Call wrong url
$smartCircuitBreaker->item("curl-item")->execute(function () {
    $url = "https://www.jgdlfkjgdfkjg.com/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        throw new \Exception($errorMessage);
    }
    curl_close($ch);
    echo $res;
});
