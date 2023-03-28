<?php
namespace Golden13\Scb;

/**
 * Scb Item.
 * Represent one circuit breaker item
 */
class ScbItem {
    /**
     * @var string
     */
    protected $_name;

    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var ScbStatus
     */
    protected $_serverStatus;

    /**
     * @var StorageInterface
     */
    protected $_storage;

    protected $_logger;

    protected $_conf = [];

    protected $_ignoredExceptions = [];

    public function __construct($name, StorageInterface $storage, $conf = []) {
        $this->_name = $name;
        $this->_storage = $storage;
        $this->_logger = Scb::getInstance()->getLogger();
        if (!empty($conf)) {
            $this->_conf = $conf;
        }
    }

    public function __destruct() {
        // write status into storage
        if (!empty($this->_storage)) {
            if (!empty($this->_serverStatus)) { // if status is empty, we don't need to save anything
                $this->_storage->set($this->_serverStatus);
            }
        }
    }

    public function getTtlForFail() {
        if (isset($this->_conf['ttlForFail'])) {
            return $this->_conf['ttlForFail'];
        } else {
            return 60;// default ttl
        }
    }

    public function addIgnoredException(\Exception $exception) {
        $this->_ignoredExceptions[] = get_class($exception);
    }

    public function getIgnoredExceptions() {
        return $this->_ignoredExceptions;
    }

    public function isEnabled() {
        return $this->_enabled ?? true;
    }

    public function execute($code) {
        if (empty($this->_serverStatus)) {
            $this->readStatus();
        }
        $this->_updateStatusByTtl(); // check if the status needs to be updated

        // if the Scb is disabled
        if (!$this->isEnabled()) {
            try {
                $res = $code();
                return $res;
            } catch (\Exception $e) {
                throw $e;
            }
        }

        try {
            if ($this->isClosed()) { // if the circuit breaker is closed
                $this->_logger->debug("CB is closed, executing code");
                $res = $code();
                $this->success();
                return $res;
            } else if ($this->isOpen()) { // open
                $this->_logger->debug("CB is open");
                if ($this->_needToTry()) {
                    $this->_logger->debug("CB is open, retrying");
                    $res = $code();
                    $this->success();
                    return $res;
                }
                $this->fail();
                throw new ScbException();
            }
        } catch (\Exception $e) {
            $this->_logger->debug("Exception in execution");
            $exceptionClassName = get_class($e);
            if (in_array($exceptionClassName, $this->_ignoredExceptions)) {
                $this->_logger->debug("Exception found in ignored list");
                $this->success($e);
                throw $e;
            } else {
                $this->fail($e);
                throw $e;
            }
        }
    }

    protected function _needToTry() {
        $time = time();
        if (($this->_serverStatus->getLastRetry() + ($this->_serverStatus->getSleep() * 1)) < $time) {
            // it's time to recheck
            return true;
        }

        return false;
    }

    public function getName() {
        return $this->_name;
    }

    public function readStatus() {
        if (!empty($this->_serverStatus)) {
            return $this->_serverStatus;
        }

        // read status stored somewhere
        $this->_serverStatus = $this->_storage->get();

        // If the lastUpdate > than ttl we should reset status
        if ($this->_serverStatus->getLastUpdate() + $this->_conf['ttlForFail'] < time()) {
            $this->_logger->debug("Resetting CB status");
            $this->_serverStatus = new ScbStatus($this->_name);
        }

        return $this->_serverStatus;
    }

    public function fail($exception = '') {
        $this->_logger->debug("Logging Fail");
        $this->_logger->error($exception);
        $this->_serverStatus->incrFailedCalls();
        // check threshold
        if ($this->_serverStatus->getFailedCalls() > $this->_conf['numberOfErrorsToOpen']) {
            // it's time to open circuit breaker
            $this->_serverStatus->setStatus(Scb::STATUS_OPEN);
            if ($this->_needToTry()) { // Updating these values only if time passed between retries
                $this->_serverStatus->setSleep($this->_getNextSleepTime());
                $this->_serverStatus->setLastRetry(time());
            }
            $this->_serverStatus->setLastUpdate(time());

            $this->_logger->debug("Set status: " . ScbTools::scbStatus2json($this->_serverStatus));
            $this->_logger->debug("Circuit breaker is open. Number of errors exceeded threshold: " . $this->_conf['numberOfErrorsToOpen']);
        }
    }

    public function success($exception = '') {
        $this->_logger->debug("Logging success");
        if (!empty($exception)) {
            $this->_logger->debug($exception);
        }
        if ($this->isOpen()) {
            // We need to close CB
            // Should we prevent cosing if there is an "good" exception?
            $this->close();
        }
    }

    protected function _getNextSleepTime() {
        $arr = $this->_conf['timeoutsToRetry'];
        $nexSleep = 0;

        $pos = array_search($this->_serverStatus->getSleep(), $arr);
        if ($pos !== false) {
            if ($pos === (count($arr) - 1)) {
                $nexSleep = $arr[$pos];
            } else {
                $nexSleep = $arr[$pos + 1];
            }
        }
        return $nexSleep;
    }

    public function close() {
        $this->_serverStatus
            ->setStatus(Scb::STATUS_CLOSED)
            ->setSleep(Scb::NO_SLEEP)
            ->setFailedCalls(0)
            ->setLastRetry(time())
            ->setLastUpdate(time());
    }

    public function isOpen() {
        if (empty($this->_serverStatus)) {
            $this->readStatus();
        }
        $this->_updateStatusByTtl(); // check if the status needs to be updated
        if ($this->_serverStatus->getStatus() === Scb::STATUS_OPEN) {
            return true;
        }
        return false;
    }

    public function isClosed() {
        if (empty($this->_serverStatus)) {
            $this->readStatus();
        }
        $this->_updateStatusByTtl(); // check if the status needs to be updated

        if ($this->_serverStatus->getStatus() === Scb::STATUS_CLOSED) {
            return true;
        }
        return false;
    }

    public function getTotalFailedCalls() {
        return $this->_serverStatus->getFailedCalls();
    }

    public function setIsEnabled($isEnabled) {
        $this->_enabled = $isEnabled;
    }

    /**
     * This method needed for the long-running processes
     * @return void
     */
    public function _updateStatusByTtl() {
        if ($this->_serverStatus->getLastUpdate() + $this->_conf['ttlForFail'] < time()) {
            $this->_logger->debug("Resetting CB status in memory");
            $this->_serverStatus = new ScbStatus($this->_name);
        }
    }

    public function getStorage() {
        return $this->_storage;
    }
}