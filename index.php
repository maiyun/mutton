<?php
declare(strict_types = 1);

define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

// --- 处理 uri ---
if ($_GET['__uri'] == 'index.html' || $_GET['__uri'] == 'index.htm' || $_GET['__uri'] == 'index.php') {
    header('Location: ./');
    exit;
}
define('URI', isset($_GET['__uri']) ? $_GET['__uri'] : '');
unset($_GET['__uri']);

// --- 国际化 ---

require 'sys/Locale.php';

// --- 正式启动 ---

require 'sys/Boot.php';
\sys\Boot::run();

// --- 加载控制器 ---

require SYS_PATH.'Route.php';
\sys\Route::run();

