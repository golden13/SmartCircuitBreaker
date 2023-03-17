<?php
namespace Golden13\Scb;

include_once "MemcacheWrapper.php";

/**
 * Memcache storage
 */
class MemcacheStorage implements StorageInterface {
    protected $_conf;

    /**
     * @var MemcacheWrapper
     */
    protected $_memcache;

    protected $_logger;

    /**
     * @var ScbItem
     */
    protected $_item;

    //protected $_savedHash = '';

    protected $_keyName;

    public function __construct($conf = []) {
        $this->_conf = $conf;
        $this->_logger = Scb::getInstance()->getLogger();
        $this->_init();
    }

    public function linkItem(ScbItem &$item) {
        $this->_item = $item;
    }

    /**
     * @param $key
     * @return ScbStatus|mixed
     */
    public function get() {
        if (empty($this->_keyName)) {
            $this->_buildKeyName();
        }
        $content = $this->_memcache->get($this->_keyName);

        try {
            $scbStatus = ScbTools::json2ScbStatus($content);
        } catch (\Exception $e) {
            $this->_logger->error('ERROR: Exception in Memcache key value: ' . $this->_keyName . ' ' . $e->getMessage());
            $scbStatus = new ScbStatus($this->_item->getName());
        }
        return $scbStatus;
    }

    protected function _buildKeyName() {
        $keyName = $this->_conf['prefix'] . $this->_item->getName();
        $this->_keyName = $keyName;
        return $keyName;
    }

    public function _init() {
        $this->_memcache = new MemcacheWrapper($this->_conf);
    }

    public function set(ScbStatus $status) {
        if (empty($this->_keyName)) {
            $this->_buildKeyName();
        }

        $json = ScbTools::scbStatus2Json($status);
        $expiry = $this->_item->getTtlForFail();
        $result = $this->_memcache->set($this->_keyName, $json, $expiry);

        if ($result === false) {
            Scb::getInstance()->getLogger()->error("Can't write serversCache file: " . $this->_keyName);
        }
    }
}

