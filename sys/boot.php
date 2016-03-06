<?php

namespace C {

	class Boot {

		public static function run() {

			ob_start();

			require 'etc/const.php';
			require ETC_PATH.'set.php';
			require ETC_PATH.'db.php';

			// --- 禁用普通页面的浏览器缓存 ---

			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: '.gmdate("D, d M Y H:i:s").' GMT');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pramga: no-cache');

			// --- 设置时区 ---

			date_default_timezone_set(TIMEZONE);

		}

	}

	// --- 处理程序突发异常 ---

	function exception() {
		/*
		if($e = error_get_last())
			logs('ERROR', $e['message'].' in '.$e['file'].' on line '.$e['line'], false);
		//*/
	}
	register_shutdown_function('\\C\\exception');

}

