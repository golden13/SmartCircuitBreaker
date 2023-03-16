<?php
namespace Golden13\Scb;

/**
 * ScbStatus
 */
class ScbStatus {
    protected $_name;

    protected $_sleep = 0;

    protected $_lastUpdate;

    protected $_lastRetry;

    protected $_failedCalls = 0;

    protected $_status; // 0 - closed, 1 - open

    public function getFailedCalls() {
        return $this->_failedCalls;
    }

    public function incrFailedCalls() {
        $this->_failedCalls++;
        return $this;
    }

    public function setFailedCalls(int $failedCalls) {
        $this->_failedCalls = $failedCalls;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastRetry() {
        return $this->_lastRetry;
    }

    /**
     * @param int $lastRetry
     */
    public function setLastRetry($lastRetry): ScbStatus {
        $this->_lastRetry = $lastRetry;
        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * @param string $name
     */
    public function setName($name): ScbStatus {
        $this->_name = $name;
        return $this;
    }

    /**
     * @return int|mixed
     */
    public function getSleep(): int {
        return $this->_sleep;
    }

    /**
     * @param int $sleep
     */
    public function setSleep(int $sleep): ScbStatus {
        $this->_sleep = $sleep;
        return $this;
    }

    /**
     * @return int
     */
    public function getLastUpdate(): int {
        return $this->_lastUpdate;
    }

    /**
     * @param int $lastUpdate
     */
    public function setLastUpdate(int $lastUpdate): ScbStatus {
        $this->_lastUpdate = $lastUpdate;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->_status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): ScbStatus {
        $this->_status = $status;
        return $this;
    }

    public function __construct($name, $status = 0, $sleep = 0, $failedCalls = 0, $lastRetry = 0, $lastUpdate = 0) {
        $this->_name = $name;
        $this->_status = $status;
        $this->_sleep = $sleep;
        $this->_failedCalls = $failedCalls;
        $this->_lastRetry = $lastRetry;
        $this->_lastUpdate = ($lastUpdate === 0)? time() : $lastUpdate;
    }
}