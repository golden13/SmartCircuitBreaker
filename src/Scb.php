<?php
namespace Golden13\Scb;

include "storage/StorageInterface.php";
include "storage/FileStorage.php";
// Not implemented yet
//include "storage/RedisStorage.php";
//include "storage/MemcacheStorage.php";
include "ScbException.php";
include "ScbItem.php";
include "ScbStatus.php";
include "ScbTools.php";

/**
 * Main class for the Smart Circuit Breaker
 */
class Scb {
    protected $_conf;

    protected $_logger = null;

    const STATUS_CLOSED = 0;

    const STATUS_OPEN = 1;

    const NO_SLEEP = 0; // default value then CB is closed

    const STORAGE_FILE = 'file';
    const STORAGE_REDIS = 'redis';
    const STORAGE_MEMCACHE = 'memcache';

    /**
     * @var array[ScbItem]
     */
    protected $_items = [];

    /**
     * @var Scb
     */
    protected static $_instance = null;

    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new Scb();
        }
        return self::$_instance;
    }

    protected function __construct() {
        $this->loadConfig();
    }

    public function setLogger($logger) {
        $this->_logger = $logger;
    }

    public function getLogger() {
        return $this->_logger;
    }

    public function loadConfig($filename = '') {
        $res = false;
        if (!empty($filename)) {
            $res = include_once $filename;
        }
        if (!$res) {
            include_once dirname(__FILE__) . "/config.default.php";
        }
        /**
         * @var array
         * SCB_CFG is a part of config.default.php
         */
        $this->_conf = $SCB_CFG;
    }

    /**
     * Return item by name. If the item is missing it will be created.
     *
     * @param string $name
     * @param StorageInterface|null $storage
     * @param array $conf
     * @return ScbItem
     * @throws ScbException
     */
    public function item($name, StorageInterface $storage = null, $conf = []) {
        if (!isset($this->_items[$name])) { // creating new
            if (empty($conf)) {
                $conf = $this->_getConfigForItem($name);
                if (empty($conf)) {
                    $this->_logger->logError("Can't find any config for the item '{$name}'");
                    throw new ScbException("Can't find any config for the item '{$name}'");
                }
            }

            if ($storage === null) {
                if (!isset($conf['storage'])) {
                    $conf['storage'] = self::STORAGE_FILE;
                }
                $storage = $this->buildStorageByType($conf['storage']);
            }
            $item = new ScbItem($name, $storage, $conf);
            $storage->linkItem($item);

            $this->_items[$name] = $item;
            return $this->_items[$name];
        }

        // return existing item
        $this->_logger->logDebug("Item '{$name}' already exists!");
        return $this->_items[$name];
    }

    protected function _getConfigForItem($name) {
        if (isset($this->_conf['items'][$name])) {
            return $this->_conf['items'][$name];
        } else {
            // check for '*'
            if (isset($this->_conf['items']['*'])) {
                return $this->_conf['items']['*'];
            }
        }
        return [];
    }

    public function buildStorageByType($type) {
        $conf = [];
        $storage = null;
        if (isset($this->_conf['storages'][$type])) {
            $conf = $this->_conf['storages'][$type];
        }
        if ($type === self::STORAGE_FILE) {
            $storage = new FileStorage($conf);
        } else if ($type === self::STORAGE_REDIS) {
            // Not implemented yet
            //$storage = new RedisStorage($this->_logger, $conf);
        } else if ($type === self::STORAGE_MEMCACHE) {
            // Not implemented yet
            //$storage = new MemcacheStorage($this->_logger, $conf);
        }
        return $storage;
    }
}