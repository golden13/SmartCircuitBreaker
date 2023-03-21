<?php
/**
 *
 * The following code shows:
 * 1. How to use your class with the Scb
 * 2. How to use PHP Arrow functions with Scb
 *
 */

include "../src/Scb.php";

use Golden13\Scb\Scb,Golden13\Scb\LogWrapper;

$scb = Scb::getInstance();

class TestClass {
    public function getServiceOne() {
        $service1URL = "http://not-existing-url1.ccc";
        $result = $this->doRequest($service1URL);
        echo $result;
    }

    public function getServiceTwo() {
        $service1URL = "http://not-existing-url2.ccc";
        $result = $this->doRequest($service1URL);
        echo $result;
    }

    public function doRequest($url) {
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
        return $res;
    }
}

$myClass = new TestClass();

// Scb is throwing the same exception which you throw from the doRequest method.
// In order co continue your code execution you have to catch it
try {
    $scb->item("service-one")->execute(fn() => $myClass->getServiceOne());
} catch (\Exception $e) {

}

try {
    $scb->item("service-two")->execute(fn() => $myClass->getServiceTwo());
} catch (\Exception $e) {

}

