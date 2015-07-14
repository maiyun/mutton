<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/7/10
 * Time: 0:12
 */

/**
 * Class Memcached
 * 本类为测试使用类，请勿在实际环境中使用
 */

class Memcached {

    var $conf = [];

    const OPT_COMPRESSION = 0;
    const OPT_BINARY_PROTOCOL = 0;

    function __construct($pool = '') {

        $conf = ['serverList'=>[],'time_last'=>time()];
        if(!is_file(ROOT_PATH.'data/Memcached/conf.txt')) file_put_contents(ROOT_PATH.'data/Memcached/conf.txt', serialize($conf));
        else $conf = unserialize(file_get_contents(ROOT_PATH.'data/Memcached/conf.txt'));
        $this->conf = $conf;
        if($pool == '') $this->conf['serverList'] = [];

    }

    function __destruct() {

        if($this->conf['time_last'] < time() - 30) $this->conf['serverList'] = [];
        $this->conf['time_last'] = time();
        file_put_contents(ROOT_PATH.'data/Memcached/conf.txt', serialize($this->conf));

    }

    function setOption($key, $val) {

    }

    function addServer($host, $port) {

        $this->conf['serverList'][] = [
            'host' => $host,
            'port' => $port
        ];

    }

    function setSaslAuthData($user, $pwd) {

    }

    function set($key, $val, $exp) {

        if($exp != 0) $exp = $exp < 2592000 ? time() + $exp : $exp;
        $content = ['exp' => $exp, 'data' => $val];
        file_put_contents(ROOT_PATH . 'data/Memcached/items/' . $key . '.txt', serialize($content));

    }

    function add($key, $val, $exp) {

    	$fn = ROOT_PATH . 'data/Memcached/items/' . $key . '.txt';
        if (file_exists($fn))
        	return false;
        if($exp != 0) $exp = $exp < 2592000 ? time() + $exp : $exp;
        $content = ['exp' => $exp, 'data' => $val];
        return file_put_contents($fn, serialize($content));

    }

    function get($key) {

        if(is_file(ROOT_PATH.'data/Memcached/items/'.$key.'.txt')) {
            $content = unserialize(file_get_contents(ROOT_PATH . 'data/Memcached/items/' . $key . '.txt'));
            if($content['exp'] > 0 && $content['exp'] < time()) {
                unlink(ROOT_PATH.'data/Memcached/items/'.$key.'.txt');
                return false;
            } else
                return $content['data'];
        } else
            return false;

    }

    function quit() {

        $this->conf['serverList'] = [];

    }

    function delete($key) {

        if(is_file(ROOT_PATH . 'data/Memcached/items/' . $key . '.txt'))
            unlink(ROOT_PATH . 'data/Memcached/items/' . $key . '.txt');

    }

    function getServerList() {

        return $this->conf['serverList'];

    }

}