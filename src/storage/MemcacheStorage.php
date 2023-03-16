<?php
namespace Golden13\Scb;

/**
 * Memcache storage
 */
class MemcacheStorage implements StorageInterface {
    protected $_conf;

    protected $_serverCacheFile  = '';

    protected $_logger;

    /**
     * @var ScbItem
     */
    protected $_item;

    protected $_savedHash = '';

    public function __construct($conf = []) {
        $this->_conf = $conf;
        $this->_logger = Scb::getInstance()->getLogger();
    }

    public function linkItem(ScbItem &$item) {
        $this->_item = $item;
    }

    /**
     * @param $key
     * @return ScbStatus|mixed
     */
    public function get() {
        $this->connect();
        // TODO: implement
    }

    public function connect() {
        //TODO: implement
    }

    public function set(ScbStatus $status) {
        // TODO: implement
    }
}

