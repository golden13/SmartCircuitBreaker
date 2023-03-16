<?php
namespace Golden13\Scb;

/**
 * Wrapper for Memcached lib
 */
class MemcacheWrapper {

    /**
     * @var Memcached
     */
    private $memcache = null;
    private $_host = '';
    private $_port = '';
    private $_igbinaryEnabled = true;

    protected static $_stime = 0;

    protected static $_count = 0;
    protected static $_session = '';

    const EXPIRE_NEVER = 0;

    /**
     * @param array $conf
     */
    public function __construct($conf) {
        $this->_host = $conf['host'];
        $this->_port = $conf['port'];
        $this->_igbinaryEnabled = $conf['igbinaryEnabled'];
    }

    /*
     * Create the Memcached Object
     * @return Memcached Object
     */
    public function connect() {
        if ($this->memcache !== null) {
            return $this->memcache;
        }

        $this->memcache = new Memcached();
        if (Memcached::HAVE_IGBINARY && $this->_igbinaryEnabled) {
            $this->memcache->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
            $this->memcache->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        }

        $this->memcache->addServer($this->_host, $this->_port);

        return $this->memcache;
    }


    public function delete($key) {
        //self::_start();
        $this->connect();

        $res = $this->memcache->delete($key);
        //self::_end("delete('{$type}', '{$key}') ");
        return $res;
    }

    /**
     * Wrapper for memcache.add().
     *
     * @param string $key
     * @param mixed  $data
     * @param int    $expire
     * @return bool True on success, false when key exists
     */
    public function add($key, $data, $expire = self::EXPIRE_NEVER) {
        //self::_start();
        $this->connect();
        $res = $this->memcache->add($key, $data, $expire);
        if (!$res) {
            Scb::getInstance()->getLogger()->debug("Error: memcache add: " . $this->memcache->getResultMessage() . $this->memcache->getResultCode());
        }
        //self::_end("add('{$type}', '{$key}', , '{$expire}') ");
        return $res;
    }

    /**
     * Wrapper for memcache.set().
     * @param string $key
     * @param mixed  $data
     * @param int    $expire
     * @return bool True on success, false when key exists
     */
    public function set($key, $data, $expire = self::EXPIRE_NEVER) {
        //self::_start();
        $this->connect();

        $res = $this->memcache->set($key, $data, $expire);
        //self::_end("set('{$type}', '{$key}', , '{$expire}') data size=" . count($data));
        return $res;
    }

    /**
     * Wrapper for memcache.get()
     * @param mixed  $keys
     * @return mixed
     */
    public function get($keys) {
        //self::_start();
        $this->connect();

        if (is_array($keys)) {
            $result = $this->memcache->getMulti($keys);
        } elseif (is_string($keys)) {
            $result = $this->memcache->get($keys);
        } else {
            return false;
        }

        //self::_end("get('{$type}', '".$str."') " . "result size = " . count($result));
        return $result;
    }


    /**
     * Start profiler
     */
    protected static function _start() {
        if (empty(self::$_session)) {
            self::$_session = md5(microtime(true));
        }
        self::$_count++;
        self::$_stime = microtime(true);
    }

    /**
     * End profiler
     * @param $message
     */
    protected static function _end($message) {
        $_etime = microtime(true) - self::$_stime;
        Scb::getInstance()->getLogger()->info("MEMCACHE: " . sprintf("%f", $_etime) . "\t\t" . self::$_session . " (".self::$_count.")\t\t" . $message);
    }
}