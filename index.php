<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2015-7-13 14:07
 * Last: 2020-1-17 01:01:20, 2023-2-4 13:17:55
 */
declare(strict_types = 1);

use sys\Route;

define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

/**
 * --- 获取和定义重写的 PATH ---
 */
define('PATH', isset($_GET['__path']) ? $_GET['__path'] : '');
unset($_GET['__path']);
$io = strpos($_SERVER['QUERY_STRING'], '&');
define('QS', $io === false ? '' : substr($_SERVER['QUERY_STRING'], $io + 1));

// --- 本地化 ---

require 'sys/locale.php';

// --- 正式启动 ---

require 'sys/boot.php';

// --- 加载控制器 ---

require SYS_PATH . 'route.php';
Route::run();

