# Smart Circuit Breaker
Different Implementation of Circuit Breaker pattern in PHP
About pattern: [https://en.wikipedia.org/wiki/Circuit_breaker_design_pattern](https://en.wikipedia.org/wiki/Circuit_breaker_design_pattern)

#### Requirements
- PHP >= 8.0
- Memcached for Memcache storage
- Any PSR-3 logger on your chose.

_**WARNING!: The project is in the beta stage so please be careful.**_

**TODO:**
1. Add more Unit tests
2. Add enable/disable logic for Scb
3. Add Redis storage
4. Add Cascading logic to prevent dependant calls
5. More examples
6. Refactoring for Logging


## Info
Scb main class is a singleton.

Initializing it you will create a main class for all your circuit breakers.
Each item, created by the _item($name)_ method, represents a separate circuit breaker with different storage and configuration.

```php
// Each item should have a unique name.
// Calling method item('xxx') will create a new item
// or return the existing one.   
$smartCircuitBreaker->item("call-google-api-service");
```

## **Configuration**

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
            // Threshold to open circuit breaker
            'numberOfErrorsToOpen' => 5,
            
            // after this amount of seconds, the status will be invalidated during the script init stage.
            'ttlForFail' => 60,
            
            // List of timeouts in seconds
            'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20,30,45,60],
            
            // Storage type: 'file', 'redis' or 'memcache'
            'storage' => 'file',
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
        // File storage
        'file' => [
            // Prefix for the file name
            'prefix' => 'scb_',
            
            // directory, no trailing slash
            'path' => '/tmp',
        ],
        
        // Memcache storage
        'memcache' => [
            // Prefix for the key name
            'prefix' => 'scb_',
            
            // Memcache server hostname
            'host' => 'localhost',
            
            // Memcache server port
            'port' => '11211',
            
            // Enable igbinary
            'igbinaryEnabled' => false,
        ],
        
        // Redis storage
        'redis' => [
            // Prefix for the key name
            'prefix' => 'scb_',
        ],
        
    ],
];
```


Many different examples can be found under _examples_ directory.

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

## Storages
For now only 2 types of storages are supported:
1. File storage
2. Memcache storage
