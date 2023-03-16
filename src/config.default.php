<?php
/**
 * Configuration for the Smart Circuit Breaker
 */
$SCB_CFG = [
    'enable' => true, // if circuit breaker logic is enabled
    'defaultLogLevel' => 'debug',

    'items' => [
        // The * corresponds to any item, excluding specified as a separate item
        '*' => [
            'numberOfErrorsToOpen' => 2, // Threshold to open circuit breaker
            'ttlForFail' => 60, // after this amount of seconds, the status will be invalidated during the script init stage.
            'timeoutsToRetry' => [0,1,2,3,4,5,10,15,20,30,45,60], // List of timeouts in seconds
            'storage' => 'file', // file | redis | memcache
        ],
        /*
         *  Example:
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
            'folder' => '/tmp', // no trailing slash
        ],
        'redis' => [
            'prefix' => 'scb_',
        ],
        'memcache' => [
            'prefix' => 'scb_',
            'server' => 'localhost:444',
        ],
    ],
];