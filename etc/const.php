<?php

define('VER', '5.0.0');

// --- 标签 ---

define('MOBILE', isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') !== false ? true : false);
define('WECHAT', isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'micromessenger') === false ? false : true);

// --- 服务端用的路径 ---

define('ROOT_PATH', substr(dirname(__FILE__), 0, -3));
define('SYS_PATH', ROOT_PATH . 'sys/');
define('ETC_PATH', ROOT_PATH . 'etc/');
define('LIB_PATH', ROOT_PATH . 'lib/');
define('MOD_PATH', ROOT_PATH . 'mod/');
define('CTR_PATH', ROOT_PATH . 'ctr/');
define('VIEW_PATH', ROOT_PATH . 'view/');
define('DATA_PATH', ROOT_PATH . 'data/');

// --- 前端用户的路径 ---

if ($_SERVER['PHP_SELF'] != '') {
    define('SITE_PATH', substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/') + 1));
} else {
    exit('[Error] Please open php.ini, change cgi.fix_pathinfo to 1.');
}
define('HTTP_PATH', '//' . $_SERVER['HTTP_HOST'] . SITE_PATH);
define('HTTP_HOST', $_SERVER['HTTP_HOST']);
define('STC_PATH', SITE_PATH . 'stc/');

