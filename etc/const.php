<?php

const VER = '6.3.0';

// --- 环境判断 ---

define('MOBILE', isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'mobile') !== false ? true : false);
define('WECHAT', isset($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'micromessenger') === false ? false : true);
define('HTTPS', isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on') ? true : false);
define('HOST', $_SERVER['HTTP_HOST']);
define('HOSTNAME', explode(':', HOST)[0]);

// --- 服务端用的路径 ---

define('ROOT_PATH', substr(dirname(__FILE__), 0, -3));
define('SYS_PATH', ROOT_PATH . 'sys/');
define('LOG_PATH', ROOT_PATH . 'log/');
define('ETC_PATH', ROOT_PATH . 'etc/');
define('LIB_PATH', ROOT_PATH . 'lib/');
define('MOD_PATH', ROOT_PATH . 'mod/');
define('CTR_PATH', ROOT_PATH . 'ctr/');
define('VIEW_PATH', ROOT_PATH . 'view/');
define('DATA_PATH', ROOT_PATH . 'data/');

// --- 前端用的路径 ---

define('URL_BASE', substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/') + 1));
define('URL_STC', URL_BASE . 'stc/');
define('URL_FULL', 'http' . (HTTPS ? 's' : '') . '://' . HOST . URL_BASE);

