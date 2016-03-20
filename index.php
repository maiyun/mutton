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
	}

}

require 'sys/boot.php';

C\Boot::run();

require SYS_PATH.'uri.php';

C\Uri::run(isset($_GET['__uri']) ? $_GET['__uri'] : '');

