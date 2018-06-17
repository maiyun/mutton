<?php
declare(strict_types = 1);

define('START_TIME', microtime(true));
define('START_MEMORY', memory_get_usage());

// --- 处理 uri ---
if ($_GET['__uri'] == 'index.html' || $_GET['__uri'] == 'index.htm') {
    header('Location: ./');
    exit;
}
if ($_GET['__uri'] == 'index.php') {
    $_GET['__uri'] = '';
}
define('URI', isset($_GET['__uri']) ? $_GET['__uri'] : '');
unset($_GET['__uri']);

require 'sys/Boot.php';
M\Boot::run();

require SYS_PATH.'Route.php';
M\Route::run();

