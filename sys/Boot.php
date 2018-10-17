<?php
declare(strict_types = 1);

namespace sys;

require 'etc/const.php';
require ETC_PATH.'set.php';

class Boot {

    public static function run(): void {

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

        // --- 设置页面缓存 ---
        if (CACHE_TTL > 0) {
            header('Expires: ' . gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + CACHE_TTL) . ' GMT');
            header('Cache-Control: max-age=' . CACHE_TTL);
        } else {
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Cache-Control: no-cache, must-revalidate');
        }

        // --- 设置时区 ---
        date_default_timezone_set(TIMEZONE);

    }

}

// --- 处理程序突发异常 ---
function exception(): void {
    if($e = error_get_last()) {
        log($e['message'] . ' in ' . $e['file'] . ' on line ' . $e['line']);
    }
}
register_shutdown_function('\\sys\\exception');

// --- 写入文件日志 ---
function log(string $msg): void {

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
    $path .= $d . '.csv';

    if(!is_file($path)) {
        file_put_contents($path, 'TIME,URL,POST,COOKIE,USER_AGENT,MESSAGE'."\n");
        chmod($path, 0777);
    }
    file_put_contents($path, '"' . date('H:i:s') . '","'.HTTP_PATH.URI.(count($_GET)?'?'.str_replace('"','""',http_build_query($_GET)):'').'","'.str_replace('"','""',http_build_query($_POST)).'","'.str_replace('"','""',http_build_query($_COOKIE)).'","'.str_replace('"','""',(isset($_SERVER['HTTP_USER_AGENT']))?$_SERVER['HTTP_USER_AGENT']:'No HTTP_USER_AGENT').'","'.str_replace('"','""',$msg)."\"\n", FILE_APPEND);

}

