# SmartCircuitBreaker
Implementation of Circuit Breaker pattern in PHP

The project is in the beta stage, please be careful.

**Example 1**:
```php
// Create an instance
$smartCircuitBreaker = Scb::getInstance();
// Add logger
$logger = new LogWrapper();
$smartCircuitBreaker->setLogger($logger);

// Wrap your code around circuit breaker
$smartCircuitBreaker->item("dummy")->execute(function () {
        // Call some service, or DB
        //throw new \Exception("Dummy exception");
    }
});

```

**Example 2**:
```php
// Create an instance
$smartCircuitBreaker = Scb::getInstance();
// Add logger
$logger = new LogWrapper();
$smartCircuitBreaker->setLogger($logger);

$smartCircuitBreaker->item("curl-item")->execute(function() {
    $url = "https://www.some-not-existing-url.com/";
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


```


Configuration for the Circuit Breaker can be found in **config.default.php** file