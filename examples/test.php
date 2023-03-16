<?php
include "../src/Scb.php";
include "../src/LoggerInterface.php";
include "../src/LogWrapper.php";

use Golden13\Scb\Scb,Golden13\Scb\LogWrapper;

// test code
$smartCircuitBreaker = Scb::getInstance();

// Simple echo Logger
// You can use any PSR-3 Logger, just change logWrapper
$logger = new LogWrapper();
$smartCircuitBreaker->setLogger($logger);

// Run code with exception in a loop
for ($i = 0; $i < 10; $i++) {
    try {
        $smartCircuitBreaker->item("dummy")->execute(function () {
            if (true) {
                throw new \Exception("Dummy exception");
            }
        });
    } catch (\Exception $e) {
        $logger->debug("Exception in test.php  i={$i}");
    }
}

// Call dummy url
$smartCircuitBreaker->item("curl-item")->execute(function() {
    $url = "https://www.jgdlfkjgdfkjg.com/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    $res = curl_exec($ch);
    if(curl_error($ch)) {
        $errorMessage = curl_error($ch);
        curl_close($ch);
        throw new \Exception($errorMessage);
    }
    curl_close($ch);
    echo $res;
});
