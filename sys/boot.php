<?php

namespace C {

    require 'etc/const.php';
    require ETC_PATH.'set.php';
    require ETC_PATH.'db.php';

    class Boot {

        public static function run() {

            //ob_start();

            // --- 设置页面缓存 ---

            if (CACHE_TTL > 0) {
                header('Expires: ' . gmdate('D, d M Y H:i:s', $_SERVER['REQUEST_TIME'] + CACHE_TTL) . ' GMT');
                header('Cache-Control: max-age=' . CACHE_TTL);
            } else {
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                header('Cache-Control: no-cache, must-revalidate');
            }
            /*
            header('Last-Modified: '.gmdate("D, d M Y H:i:s").' GMT');
            header('Pramga: no-cache');
            */

            // --- 设置时区 ---

            date_default_timezone_set(TIMEZONE);

        }

    }

    // --- 处理程序突发异常 ---

    function exception() {
        if($e = error_get_last())
            log($e['message'] . ' in ' . $e['file'] . ' on line ' . $e['line']);
    }
    register_shutdown_function('\\C\\exception');

    function log($msg) {

        list($y, $m, $d) = explode('-', date('Y-m-d'));
        $path = SYS_PATH . 'log/' . $y . '/';
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

}

