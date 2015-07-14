<?php

/**
 * VERSION: [1.0]
 */

ob_start();

// --- 定义常量 ---

define('ROOT_PATH', substr(dirname(__FILE__), 0, -5));
define('LIB_PATH', ROOT_PATH . 'librarys/');
define('MOD_PATH', ROOT_PATH . 'models/');
define('CONTROL_PATH', ROOT_PATH . 'controllers/');
define('USING_SYSTEM', true);
define('AJAX', (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest") ? true : false);
define('SITE_PATH', substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/') + 1));
define('IMAGES_PATH', SITE_PATH . 'images/');
define('HTTP_PATH', 'http://' . $_SERVER['HTTP_HOST'] . SITE_PATH);
define('PAGE_START_TIME', microtime(true));

// --- 禁用普通页面的浏览器缓存 ---

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate("D, d M Y H:i:s").' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pramga: no-cache');

// --- 加载配置文件 ---

require(ROOT_PATH.'confs/db.php');
require(ROOT_PATH.'confs/kv.php');

// --- 设置时区 ---

date_default_timezone_set('Asia/Shanghai');

// --- 处理异常中断错误，写入文件 ---

function exception_handler() {
    if($e = error_get_last())
        logs('SHUTDOWN', $e['message'].' in <b>'.$e['file'].'</b> on line <b>'.$e['line'].'</b>', false);
}
register_shutdown_function('exception_handler');

// --- 实现加载类库、模块 ---

$_L = NULL;
$_M = NULL;

class LC {

    function load($path, $auto = true) {

        global $_L;
        if (strpos($path, '/') !== false) $name = substr($path, strrpos($path, '/') + 1);
        else $name = $path;
        if (!isset($_L->$name)) {
            if (is_file(ROOT_PATH . 'librarys/' . $path . '.php')) {
                require(ROOT_PATH . 'librarys/' . $path . '.php');
                if($auto) {
                    $cname = '\\Chameleon\\Library\\' . $name;
                    $_L->$name = new $cname;
                }
            } else
                logs('L(load)', 'Library ('.$name.') not found.', $path);
        }

    }
}

class MC {

    function load($path, $auto = true) {

        global $_M;
        if(strpos($path, '/') !== false) $name = substr($path, strrpos($path, '/') + 1);
        else $name = $path;
        if (!isset($_M->$name)) {
            if(is_file(ROOT_PATH . 'models/' . $path . '.php')) {
                require(ROOT_PATH . 'models/' . $path . '.php');
                if($auto) {
                    $mname = '\\Chameleon\\Module\\' . $name;
                    $_M->$name = new $mname;
                }
            } else
                logs('M(load)', 'Models not found.', $path);
        }

    }

}

$_L = new LC();
$_M = new MC();

// --- 加载模拟器 ---

if(EMULATOR_MEMCACHED === true && !class_exists('Memcached')) require(LIB_PATH.'Memcached/Emulator.php');

// --- 获取 CONTROLLER、ACTION 和 PARAM 数组 ---

$_PATH = isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : '';
$pos = strpos($_PATH, '&');
if($pos !== false) {
    parse_str(substr($_PATH, $pos+1), $_GET);
    $_PATH = substr($_PATH, 0, $pos);
}
unset($pos);
$_PATH = explode('/', $_PATH);

$_CONTROLFOLDER = '';
$_CONTROLLER = ($_PATH[0] == '') ? 'Index' : ucfirst($_PATH[0]);
if(is_dir(CONTROL_PATH.$_CONTROLLER)) {
    $_CONTROLFOLDER = $_CONTROLLER . '/';
    $_PATH = array_slice($_PATH, 1);
    $_CONTROLLER = ($_PATH[0] == '') ? 'Index' : ucfirst($_PATH[0]);
}
$_ACTION = isset($_PATH[1]) ? $_PATH[1] : 'index';
$_ACTION = ($_ACTION=='') ? 'index' : $_ACTION;
$_PARAM = array_slice($_PATH, 2);

// --- 开始主控制器 ---

$_VIEW = '';

Class Main {

    var $param = [];
    var $action = '';
    var $json = ['result'=>'1'];

    function __construct() {

        global $_ACTION, $_PARAM;

        $this->param = $_PARAM;
        $this->action = $_ACTION;

    }

    function __destruct() {

        global $_L, $_VIEW;
        if(isset($_L->Session))
            $_L->Session = NULL;

        $_VIEW .= ob_get_clean();
        echo $_VIEW;

    }

    function echoJson() {

        header('Content-type: application/json; charset=utf-8');
        echo json_encode($this->json, JSON_UNESCAPED_UNICODE);

    }

    function loadView($path, $data = array(), $return = false) {

        global $_VIEW;
        $_VIEW .= ob_get_clean();
        ob_start();

        // --- 重构 loadView(string $path, boolen $return) ---
        if(is_array($data)) extract($data);
        else $return = $data;

        require(ROOT_PATH . 'views/' . $path . '.php');

        $v = ob_get_clean();
        ob_start();
        if($return) return $v ;
        else $_VIEW .= $v;

    }

    function post($name, $v = '') {
        if(isset($_POST[$name])) return $_POST[$name];
        else return $v;
    }
	
}

/**
 * @return MI
 */
function M() {
    global $_M;
    return $_M;
}

/**
 * @return LI
 */
function L() {
    global $_L;
    return $_L;
}

// --- 系统方法 ---

// --- 日志 ---
function logs($title, $message, $enmsg = '', $exit = true) {

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
    $path .= $d . '/';
    if(!is_dir($path)) {
        mkdir($path, 0777);
        chmod($path, 0777);
    }
    $path .= date('H') . '.html';

    file_put_contents($path, '<b>' . date('H:i:s') . '</b>: <b>'.$title.'</b> '.$message.(($enmsg != '')?'<br>Security string: <b>'.$enmsg.'</b>':'').'<br><br>'."\r\n", FILE_APPEND);
    if($exit) exit('<b>'.$title.'</b> ' . $message . (($enmsg != '')?' Please see <b>'.str_replace(ROOT_PATH,'',$path).'</b>.':''));

}

// --- 临时打印 pre 变量 ---
function pre($obj) {
    echo '<div style="background-color:#FFF;border:solid 1px #000;padding:20px;font-size:14px;color:#000;line-height:1;max-width:1000px;overflow:scroll;font-family:\'Microsoft YaHei UI\',\'Microsoft YaHei\',SimSun,\'Segoe UI\',Tahoma,Helvetica,Sans-Serif;line-height:20px;"><div style="text-align:center;font-weight:bold;">PRE</div><pre style="margin:20px 0 0 0;">';
    print_r($obj);
    echo '</pre></div>';
}

// --- 临时打印简短变量 ---
function pre_e($txt) {
    echo '【'.$txt.'】';
}

