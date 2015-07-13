<?php

/**
 * Class Index
 */

class Index extends Main {

    function __construct() {

        parent::__construct();

        L()->load('Memcached');

        L()->Memcached->connect();

    }

    function index() {

        $this->loadView('index');

        echo '测试 Memcached Emulatour';

        pre(L()->Memcached->getServerList());
        L()->Memcached->set('abc', '呵呵哒', 20);
        pre_e(L()->Memcached->get('abc'));

    }

}

