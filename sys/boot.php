<?php
/**
 * Project: Mutton (#f49292), User: JianSuoQiYue
 * Date: 2018-6-17 23:29
 * Last: 2020-1-17 01:00:43, 2022-3-22 23:31:04
 */
declare(strict_types = 1);

namespace sys;

require 'etc/const.php';
require ETC_PATH.'set.php';

// --- 自动加载类、模型 ---
spl_autoload_register(function (string $name) {
    $type = substr($name, 0, 3);
    $cn = strtolower(str_replace('\\', '/', substr($name, 4)));
    switch($type) {
        case 'lib': {
            require LIB_PATH . $cn . '.php';
            break;
        }
        case 'mod': {
            require MOD_PATH . $cn . '.php';
            break;
        }
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
    $clientIp = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);

    list($y, $m, $d, $h) = explode('-', date('Y-m-d-H'));
    $path = LOG_PATH . $y . '/';
    if(!is_dir($path)) {
        if (!@mkdir($path, 0777)) {
            return;
        }
        @chmod($path, 0777);
    }
    $path .= $m . '/';
    if(!is_dir($path)) {
        if (!@mkdir($path, 0777)) {
            return;
        }
        @chmod($path, 0777);
    }
    $path .= $d . '/';
    if(!is_dir($path)) {
        if (!@mkdir($path, 0777)) {
            return;
        }
        @chmod($path, 0777);
    }
    $path .= $h . $fend . '.csv';

    if(!is_file($path)) {
        if (!@file_put_contents($path, 'TIME,UNIX,URL,RAWPOST,POST,COOKIE,USER_AGENT,REALIP,CLIENTIP,MESSAGE'."\n")) {
            return;
        }
        @chmod($path, 0777);
    }
    @file_put_contents($path, '"' .
        date('H:i:s') . '","' .
        time().'","' .
        URL_FULL . PATH . (count($_GET) ? '?' . str_replace('"', '""', http_build_query($_GET)) : '') . '","' .
        str_replace('"', '""', file_get_contents('php://input')) . '","' .
        str_replace('"', '""', json_encode($_POST)) . '","' .
        str_replace('"', '""', http_build_query($_COOKIE)) . '","' .
        str_replace('"', '""', (isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : 'No HTTP_USER_AGENT') . '","' .
        str_replace('"', '""', $realIp) . '","' .
        str_replace('"', '""', $clientIp) . '","' .
        str_replace('"', '""', $msg) . "\"\n", FILE_APPEND);
}
log('', '-visit');

