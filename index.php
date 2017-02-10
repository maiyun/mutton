<?php

// --- 自动加载类、模型 ---

function __autoload($name) {

	$type = substr($name, 0, 5);
	$cn = substr($name, 6);
	switch($type) {
		case 'C\\lib': {
			require LIB_PATH . $cn.'.php';
			break;
		}
		case 'C\\mod': {
			require MOD_PATH . $cn.'.php';
			break;
		}
		default: {
			$name = str_replace('\\', '/', $name);
			require CTR_PATH . $name . '.php';
			break;
		}
	}

}

require 'sys/boot.php';

C\Boot::run();

require SYS_PATH.'route.php';

define('URI', isset($_GET['__uri']) ? $_GET['__uri'] : '');
unset($_GET['__uri']);

C\Route::run();

