<?php
namespace Golden13\Scb;

/**
 * LogWrapper class
 */
class LogWrapper implements LoggerInterface {

    public function __construct() {
    }

    public function error($message, array $context = array()) {
        echo $message, PHP_EOL;
    }

    public function info($message, array $context = array()) {
        echo $message, PHP_EOL;
    }

    public function debug($message, array $context = array()) {
        echo $message, PHP_EOL;
    }

    public function emergency($message, array $context = array()) {
        // TODO: Implement emergency() method.
    }

    public function alert($message, array $context = array()) {
        // TODO: Implement alert() method.
    }

    public function critical($message, array $context = array()) {
        // TODO: Implement critical() method.
    }

    public function warning($message, array $context = array()) {
        // TODO: Implement warning() method.
    }

    public function notice($message, array $context = array()) {
        // TODO: Implement notice() method.
    }

    public function log($level, $message, array $context = array()) {
        // TODO: Implement log() method.
    }
}