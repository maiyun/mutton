<?php

// --- Db ---

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8');
define('DB_DBNAME', 'net.maiyun.os');
define('DB_PRE', 'test_');

define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');

// --- Memcached ---

define('MC_HOST', '127.0.0.1');
define('MC_PORT', 3306);
define('MC_POOL', '');
define('MC_PRE', 'cd_');

define('MC_USERNAME', 'root');
define('MC_PASSWORD', '');

// --- Redis ---

define('RD_HOST', '127.0.0.1');
define('RD_PORT', 6379);
define('RD_USER', 'root');
define('RD_PWD', 'pwd');
define('RD_INDEX', 0);

// --- OSS ---

define('OSS_ACCESS_KEY_ID', '');
define('OSS_ACCESS_KEY_SECRET', '');
define('OSS_ENDPOINT', '');
define('OSS_BUCKET', '');

// --- Session ---

define('SESSION_NAME', 'CHA_SESSION');
define('SESSION_MEM', false);

// --- Mail ---

define('MAIL_HOST', 'smtp.xxx.com');
define('MAIL_USER', 'chameleon@xxx.com');
define('MAIL_PWD', 'xxxxxx');

// --- AliyunMNS ---

define('MNS_ACCESS_ID', '');
define('MNS_ACCESS_KEY', '');
define('MNS_ENDPOINT', '');  // eg. http://1234567890123456.mns.cn-shenzhen.aliyuncs.com

