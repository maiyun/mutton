<?php

namespace C {

	class Uri {

		public static function run() {

			unset($_GET['__uri']);

			// --- 组成默认的 uri 字符串 ---

			$uri = URI;
			if($uri == '')
				$uri = DEFAULT_APP.'/'.DEFAULT_BIN.'/'.DEFAULT_ACT;
			else
				if(SINGLE_APP)
					$uri = DEFAULT_APP . '/' . $uri;
			$uriArray = explode('/', $uri);

			$app = $uriArray[0];
			$bin = $uriArray[1];
			$act = isset($uriArray[2]) && $uriArray[2] != '' ? $uriArray[2] : DEFAULT_ACT;
			$param = (count($uriArray) > 3) ? array_slice($uriArray, 3) : [];

			// --- 加载相关的控制器文件并运行 ---

			require SYS_PATH.'ctr.php';

			require CTR_PATH.$app.'/'.$bin.'.php';
			$ctrName = '\\'.$app.'\\'.$bin;
			$ctr = new $ctrName;
			$ctr->param = $param;
			$ctr->action = $act;

			// --- 强制 HTTPS ---

			if ((MUST_HTTPS && $ctr->mustHttps()) || !MUST_HTTPS) {

				if (method_exists($ctr, $act))
					$ctr->$act();
				else {
					// --- 如果控制器方法不存在,则查看 remap 是否存在 ---
					// --- remap 存在则交给 remap ---
					if (method_exists($ctr, '__remap')) {
						$ctr->__remap();
					} else {
						header('Location: ' . SITE_PATH);
					}
				}

			}

		}

	}

}

