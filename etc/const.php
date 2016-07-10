<?php

define('VER', '2.2.1');

define('START_TIME', microtime(true));

define('MOBILE', strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') !== false ? true : false);

// --- 服务端用的路径 ---

define('ROOT_PATH', substr(dirname(__FILE__), 0, -3));
define('SYS_PATH', ROOT_PATH . 'sys/');
define('ETC_PATH', ROOT_PATH . 'etc/');
define('LIB_PATH', ROOT_PATH . 'lib/');
define('MOD_PATH', ROOT_PATH . 'mod/');
define('CTR_PATH', ROOT_PATH . 'ctr/');
define('VIEW_PATH', ROOT_PATH . 'view/');

// --- 前端用户的路径 ---

define('SITE_PATH', substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/') + 1));
define('HTTP_PATH', 'http://' . $_SERVER['HTTP_HOST'] . SITE_PATH);
define('IMG_PATH', SITE_PATH . 'img/');

