<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:00:43
 */
declare(strict_types = 1);

namespace sys;

require 'etc/const.php';
require ETC_PATH.'set.php';

// --- 自动加载类、模型 ---
spl_autoload_register(function (string $name) {
    $type = substr($name, 0, 3);
    $cn = str_replace('\\', '/', substr($name, 4));
    switch($type) {
        case 'lib':
            require LIB_PATH . $cn . '.php';
            break;
        case 'mod':
            require MOD_PATH . $cn . '.php';
            break;
    }
}, true);

// --- 设置时区 ---
date_default_timezone_set(TIMEZONE);

// --- 处理程序突发异常 ---
function exception(): void {
    if($e = error_get_last()) {
        log($e['message'] . ' in ' . $e['file'] . ' on line ' . $e['line'], '-error');
    }
}
register_shutdown_function('\\sys\\exception');

// --- 写入文件日志 ---
function log(string $msg, string $fend = ''): void {

    $realIp = $_SERVER['REMOTE_ADDR'];
    $twoIp = isset($_SERVER['HTTP_X_CONNECTING_IP']) ? $_SERVER['HTTP_X_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
    $clientIp = isset($_SERVER['HTTP_X_REAL_FORWARDED_FOR']) ? $_SERVER['HTTP_X_REAL_FORWARDED_FOR'] : (isset($_SERVER['HTTP_CLIENT_IP']) ? $_SERVER['HTTP_CLIENT_IP'] : $twoIp);

    list($y, $m, $d) = explode('-', date('Y-m-d'));
    $path = LOG_PATH . $y . '/';
    if(!is_dir($path)) {
        mkdir($path, 0777);
        chmod($path, 0777);
    }
    $path .= $m . '/';
    if(!is_dir($path)) {
        mkdir($path, 0777);
        chmod($path, 0777);
    }
    $path .= $d . $fend . '.csv';

    if(!is_file($path)) {
        file_put_contents($path, 'TIME,URL,COOKIE,USER_AGENT,REALIP,TWOIP,CLIENTIP,MESSAGE'."\n");
        chmod($path, 0777);
    }
    @file_put_contents($path, '"' . date('H:i:s') . '","' . URL_FULL . URI . (count($_GET) ? '?' . str_replace('"', '""', http_build_query($_GET)) : '') . '","' . str_replace('"', '""', http_build_query($_COOKIE)) . '","' . str_replace('"', '""', (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'No HTTP_USER_AGENT') . '","' . str_replace('"', '""', $realIp) . '","' . str_replace('"', '""', $twoIp) . '","' . str_replace('"', '""', $clientIp) . '","' . str_replace('"', '""', $msg) . "\"\n", FILE_APPEND);

}
log('', '-visit');

