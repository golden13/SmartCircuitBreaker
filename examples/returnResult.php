<?php
/**
 *
 * The following code shows:
 * 1. How to use your class with the Scb
 * 2. How to return result from your method executed inside Scb
 * 2. How to use PHP Arrow functions with Scb
 *
 */

include "../src/Scb.php";

use Golden13\Scb\Scb,Golden13\Scb\LogWrapper;

$scb = Scb::getInstance();

class TestClass {
    public function getGoogleHomePage() {
        $service1URL = "http://www.google.com";
        $result = $this->doRequest($service1URL);
        return $result;
    }

    public function getNotExistingUrl() {
        $service1URL = "http://not-existing-url2.ccc";
        $result = $this->doRequest($service1URL);
        return $result;
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
    // $r will return $result from the method
    $r = $scb->item("google-home-page")->execute(function () use ($myClass) {
        $result = $myClass->getGoogleHomePage();
        return $result;
    });
    var_dump($r);
} catch (\Exception $e) {

}

// Example with Arrow Function
try {
    // $r will have result of the method execution
    $r = $scb->item("not-existing-url")->execute(fn() => $myClass->getNotExistingUrl());
    var_dump($r);
} catch (\Exception $e) {

}

