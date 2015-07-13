<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/7/4
 * Time: 22:48
 */

// --- Memcached 为长连接进城池 ---

if (!defined('USING_SYSTEM')) exit;

class L_Memcached {

    var $link = NULL;

    // --- 可修改变量 ---

    var $host = '';
    var $pre = '';
    var $port = '';
    var $user = '';
    var $pwd = '';
    var $pool = '';

    function __construct($pool = '') {

        $this->pool = $pool == '' ? MCPOOL : $pool;
        if($pool === NULL)
            $this->link = new Memcached();
        else
            $this->link = new Memcached($pool);

    }

    function isConnect() {

        if (!count($this->link->getServerList()))
            return false;
        else
            return true;

    }

    function connect() {

        if(!$this->isConnect()) {
            $this->host = $this->host == '' ? MCHOST : $this->host;
            $this->port = $this->port == 0 ? MCPORT : $this->port;
            $this->user = $this->user == '' ? MCUSER : $this->user;
            $pwd = $this->pwd == '' ? MCPW : $this->pwd;
            $this->pre = $this->pre == '' ? MCPRE : $this->pre;

            $this->link->setOption(Memcached::OPT_COMPRESSION, false);
            $this->link->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $this->link->addServer($this->host, $this->port);
            if ($this->user != '' && $pwd != '')
                $this->link->setSaslAuthData($this->user, $pwd);
        }

    }

    function set($key, $val, $exp = 0) {

        $this->link->set($key, $val, $exp);

    }

    function get($key) {

        return $this->link->get($key);

    }

    function quit() {

        $this->link->quit();
        $this->link = NULL;

    }

    function delete($key) {

        $this->link->delete($key);

    }

    function getServerList() {

        if($this->link !== NULL)
            return $this->link->getServerList();
        else return [];

    }

}