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
        $this->_storage->set($this->_serverStatus);
    }

    public function addIgnoredException(\Exception $exception) {
        $this->_ignoredExceptions[] = get_class($exception);
    }

    public function getIgnoredExceptions() {
        return $this->_ignoredExceptions;
    }

    public function execute($code) {
        if (empty($this->_serverStatus)) {
            $this->readStatus();
        }

        try {
            if ($this->isClosed()) { // if the circuit breaker is closed
                $this->_logger->logDebug("CB is closed, executing code");
                $res = $code();
                $this->success();
                return $res;
            } else if ($this->isOpen()) { // open
                $this->_logger->logDebug("CB is open");
                if ($this->_needToTry()) {
                    $this->_logger->logDebug("CB is open, retrying");
                    $res = $code();
                    $this->success();
                    return $res;
                }
                $this->fail();
                throw new ScbException();
            }
        } catch (\Exception $e) {
            $this->_logger->logDebug("Exception in execution");
            $exceptionClassName = get_class($e);
            if (in_array($exceptionClassName, $this->_ignoredExceptions)) {
                $this->_logger->logDebug("Exception found in ignored list");
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
            $this->_logger->logDebug("Resetting CB status");
            $this->_serverStatus = new ScbStatus($this->_name);
        }

        return $this->_serverStatus;
    }

    public function fail($exception = '') {
        $this->_logger->logDebug("Logging Fail");
        $this->_logger->logError($exception);
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

            $this->_logger->logDebug("Set status: " . ScbTools::scbStatus2json($this->_serverStatus));
            $this->_logger->logDebug("Circuit breaker is open. Number of errors exceeded threshold: " . $this->_conf['numberOfErrorsToOpen']);
        }
    }

    public function success($exception = '') {
        $this->_logger->logDebug("Logging success");
        if (!empty($exception)) {
            $this->_logger->logDebug($exception);
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

        if ($this->_serverStatus->getStatus() === Scb::STATUS_OPEN) {
            return true;
        }
        return false;
    }

    public function isClosed() {
        if (empty($this->_serverStatus)) {
            $this->readStatus();
        }
        if ($this->_serverStatus->getStatus() === Scb::STATUS_CLOSED) {
            return true;
        }
        return false;
    }

}