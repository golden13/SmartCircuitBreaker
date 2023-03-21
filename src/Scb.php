<?php
namespace Golden13\Scb;

include_once "storage/StorageInterface.php";
include_once "storage/FileStorage.php";
// Not implemented yet
//include_once "storage/RedisStorage.php";
include_once "storage/MemcacheStorage.php";
include_once "ScbException.php";
include_once "ScbItem.php";
include_once "ScbStatus.php";
include_once "ScbTools.php";
include_once "LoggerInterface.php";
include_once "LogWrapper.php";


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

    protected function _loadDefaultLogger(): void {
        if (empty($this->_logger)) {
            $this->_logger = new LogWrapper();
        }
    }

    /**
     * @param string|array $source  path to config file, or config array
     * @return void
     */
    public function loadConfig(array|string $source = ''): void {
        $this->_loadDefaultLogger();
        $res = false;
        $config = [];

        if (!empty($source)) {
            if (is_array($source)) {
                $config = $source;
            } else {
                $config = include $source;
            }
        } else {
            $config = include dirname(__FILE__) . "/config.default.php";
        }

        $this->_conf = $config;
    }

    public function getConfig() {
        return $this->_conf;
    }

    public function isEnabled() {
        if ($this->_conf['enabled']) {
            return true;
        }
        return false;
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
                    $this->_logger->error("Can't find any config for the item '{$name}'");
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
        $this->_logger->debug("Item '{$name}' already exists!");
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
            //$storage = new RedisStorage($conf);
        } else if ($type === self::STORAGE_MEMCACHE) {
            $storage = new MemcacheStorage($conf);
        }
        return $storage;
    }
}