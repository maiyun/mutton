<?php
/**
 * User: JianSuoQiYue
 * Date: 2017/07/04 22:48
 * Last: 2018-7-28 15:15:37
 */
declare(strict_types = 1);

namespace lib;

require ETC_PATH.'memcached.php';

class Memcached {

    private static $_poll = [];

    /* @var $_link \Memcached */
    private $_link = NULL;

    private $_pre = '';

    /**
     * @param string $name
     * @param array $opt
     * @return Memcached
     * @throws \Exception
     */
    public static function get(array $opt = [], ?string $name = NULL): Memcached {
        if ($name !== NULL) {
            if (isset(self::$_poll[$name])) {
                return self::$_poll[$name];
            } else {
                $link = new Memcached();
                $link->connect($opt);
                self::$_poll[$name] = $link;
                return self::$_poll[$name];
            }
        } else {
            $link = new Memcached();
            $link->connect($opt);
            return $link;
        }
    }

    public function isConnect() {

        if (!count($this->_link->getServerList()))
            return false;
        else
            return true;

    }

    /**
     * @param array $opt
     * @return bool
     * @throws \Exception
     */
    public function connect(array $opt = []): bool {

        $host = isset($opt['host']) ? $opt['host'] : MC_HOST;
        $user = isset($opt['user']) ? $opt['user'] : MC_USER;
        $pwd = isset($opt['pwd']) ? $opt['pwd'] : MC_PWD;
        $pool = isset($opt['pool']) ? $opt['pool'] : MC_POOL;
        $port = isset($opt['port']) ? $opt['port'] : MC_PORT;
        $this->_pre = isset($opt['pre']) ? $opt['pre'] : MC_PRE;

        if ($pool != '') {
            $this->_link = new \Memcached();
        } else {
            $this->_link = new \Memcached($pool);
        }

        $this->_link->setOption(\Memcached::OPT_COMPRESSION, false);
        $this->_link->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);

        if ($this->_link->addServer($host, $port)) {
            if (($user != '') && ($pwd != '')) {
                $this->_link->setSaslAuthData($user, $pwd);
            }
            return true;
        } else {
            throw new \Exception('[Error] Connect failed.');
        }

    }

    public function addValue(string $key, $val, int $exp = 0): bool {

        return $this->_link->add($this->_pre.$key, $val, $exp);

    }

    public function setValue(string $key, string $val, int $exp = 0): void {

        $this->_link->set($this->_pre . $key, $val, $exp);

    }

    public function getValue(string $key) {

        return $this->_link->get($this->_pre . $key);

    }

    public function quit(): void {

        $this->_link->quit();
        $this->_link = NULL;

    }

    public function delete(string $key): void {

        $this->_link->delete($this->_pre . $key);

    }

    public function getServerList() {

        if ($this->_link !== NULL) {
            return $this->_link->getServerList();
        } else {
            return [];
        }

    }

}

