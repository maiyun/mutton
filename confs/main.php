<?php

// --- 实现加载类库、模块、模型 ---

$_D = NULL;

class DC {

    function load($path, $auto = true) {

        global $_D;
        if(strpos($path, '/') !== false) $name = substr($path, strrpos($path, '/') + 1);
        else $name = $path;
        if (!isset($_D->$name)) {
            if(is_file(ROOT_PATH . 'drives/' . $path . '.php')) {
                require(ROOT_PATH . 'drives/' . $path . '.php');
                if($auto) {
                    $dname = '\\Chameleon\\Drive\\' . $name;
                    $_D->$name = new $dname;
                }
            } else
                logs('D(load)', 'Drive not found.', $path);
        }

    }

}

$_D = new DC();

require(MOD_PATH.'Model.php');

// --- 开始主控制器 ---

/**
 * @return DI
 */
function D() {
    global $_D;
    return $_D;
}

// --- 系统方法 ---

// --- 日志 ---
function logs($title, $message, $enmsg = '', $exit = true) {

    global $_PATH_STR, $_GET_STR;

    // --- 重构 logs(string $title, string $message, bool $exit = true) ---
    if(is_bool($enmsg)) {
        $exit = $enmsg;
        $enmsg = '';
    }

    list($y, $m, $d) = explode('-', date('Y-m-d'));
    $path = ROOT_PATH . 'logs/' . $y . '/';
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

    if(!is_file($path)) file_put_contents($path, 'TIME,URL,POST,COOKIE,TITLE,MESSAGE'."\n");
    file_put_contents($path, '"' . date('H:i:s') . '","'.HTTP_PATH.$_PATH_STR.(isset($_GET_STR)?'?'.$_GET_STR:'').'","'.str_replace('"','""',http_build_query($_POST)).'","'.str_replace('"','""',http_build_query($_COOKIE)).'","'.str_replace('"','""',$title).'","'.str_replace('"','""',$message).(($enmsg != '')?',Security string: '.str_replace('"','""',$enmsg):'').'"'."\n", FILE_APPEND);
    if($exit) exit('<b>'.$title.'</b> ' . $message . (($enmsg != '') ? ' Please see <b>'.str_replace(ROOT_PATH, '', $path).'</b>.' : ''));

}

