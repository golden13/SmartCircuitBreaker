<?php
namespace Golden13\Scb;

/**
 * File Storage class. Provides methods to store ScbStatus on a file system.
 */
class FileStorage implements StorageInterface {
    /**
     * @var array
     */
    protected $_conf;

    /**
     * @var string
     */
    protected $_serverCacheFile  = '';

    protected $_logger;

    /**
     * @var ScbItem
     */
    protected $_item;

    /**
     * @var string
     */
    protected $_savedHash = '';

    public function __construct($conf = []) {
        $this->_conf = $conf;
        $this->_logger = Scb::getInstance()->getLogger();
    }

    /**
     * Link ScbItem to Storage
     * @param ScbItem $item
     * @return void
     */
    public function linkItem(ScbItem &$item) {
        $this->_item = $item;
    }

    protected function _buildCacheFileName() {
        // /tmp/scb_xxxx
        $cacheName = $this->_conf['path'] . '/' . $this->_conf['prefix'] . $this->_item->getName();
        $this->_serverCacheFile = $cacheName;
    }

    /**
     * Get status info from the file
     * @param $key
     * @return ScbStatus|mixed
     */
    public function get() {
        if (empty($this->_serverCacheFile)) {
            $this->_buildCacheFileName();
        }

        $content = @file_get_contents($this->_serverCacheFile);
        $this->_savedHash = md5($content);
        try {
            $scbStatus = ScbTools::json2ScbStatus($content);
        } catch (\Exception $e) {
            $this->_logger->error('ERROR: Exception in serversCache file: ' . $this->_serverCacheFile . ' ' . $e->getMessage());
            $scbStatus = new ScbStatus($this->_item->getName());
        }
        return $scbStatus;
    }

    /**
     * Not needed here
     * @return void
     */
    public function connect() {
    }

    /**
     * Persists status info to the file system
     * @param ScbStatus $status
     * @return void
     */
    public function set(ScbStatus $status) {
        if (empty($this->_serverCacheFile)) {
            $this->_buildCacheFileName();
        }

        $json = ScbTools::scbStatus2Json($status);
        $newHash = md5($json);
        if ($this->_savedHash === $newHash) {
            $this->_logger->debug("No changes in CB status. The file will not be updated");
            return;
        }

        $result = file_put_contents($this->_serverCacheFile . '.tmp', $json);
        if (!@rename($this->_serverCacheFile . '.tmp', $this->_serverCacheFile)) {
            @unlink($this->_serverCacheFile);
            $result = @rename($this->_serverCacheFile . '.tmp', $this->_serverCacheFile);
        }

        if ($result === false) {
            Scb::getInstance()->getLogger()->logError("Can't write serversCache file: " . $this->_serverCacheFile);
        }
    }
}

