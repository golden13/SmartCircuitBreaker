# SmartCircuitBreaker
Different Implementation of Circuit Breaker pattern in PHP

Scb class is a singleton.

Initializing it you will create a main class for all your circuit breakers.
Each item, created by the _item($name)_ method, represents a separate circuit breaker with different storage and configuration.

```php
// Each item should have a unique name.
// Calling method item('xxx') will create a new item
// or return the existing one.   
$smartCircuitBreaker->item("my-code-part1");
```

**Configuration**

Scb configuration is a php array. By default, the Scb uses the configuration stored in **config.default.php**

```php
[
    // Enable circuit breaker logic
    'enable' => true,
    
    // default log level
    'defaultLogLevel' => 'debug',

    // list of items
    'items' => [
        // The * corresponds to any item, excluding specified as a separate item
        '*' => [
            'numberOfErrorsToOpen' => 2, // Threshold to open circuit breaker
            'ttlForFail' => 60, // after this amount of seconds, the status will be invalidated during the script init stage.
            'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20,30,45,60], // List of timeouts in seconds
            'storage' => 'file', // file | redis | memcache
        ],
        /*
         *  Example of custom item:
         * 'custom-item' => [
         *      'numberOfErrorsToOpen' => 12,
                'ttlForFail' => 60,
                'timeoutsToRetry' => [0,1,2,3,4,5,10],
                'storage' => 'file',
         * ]
         */
    ],
    // Storages
    'storages' => [
        'file' => [
            'prefix' => 'scb_',
            'path' => '/tmp', // no trailing slash
        ],
        'redis' => [
            'prefix' => 'scb_',
        ],
        'memcache' => [
            'prefix' => 'scb_',
            'host' => 'localhost',
            'port' => '11211',
            'igbinaryEnabled' => false,
        ],
    ],
];
```

_**The project is in the beta stage, please be careful.**_

**Example 0**
```php
// Create an instance
$smartCircuitBreaker = Scb::getInstance();
// If no logger specified, the Smart Circuit Breaker will use the default logger (LogWrapper.php)
// Some code inside the circuit breaker
$smartCircuitBreaker->item("dummy")->execute(function () {
        // Call some service, or DB
        //throw new \Exception("Dummy exception");
    }
});
```

**Example 1**:
```php
// Create an instance
$smartCircuitBreaker = Scb::getInstance();

// Add logger
$logger = new LogWrapper();
$smartCircuitBreaker->setLogger($logger);

// Put your code inside the circuit breaker
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

// Put your code inside the circuit breaker
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
