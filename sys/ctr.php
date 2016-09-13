<?php

namespace C {

	use C\lib\Aes;
	use C\lib\Session;

	class ctr {

		var $param = [];
		var $action = "";
		var $json = ['result' => '1'];

		protected function writeJson($result, $data = []) {
			header('Content-type: application/json; charset=utf-8');
			$this->json['result'] = $result + 0;
			if($result <= 0)
				$this->json['msg'] = $data;
			else
				$this->json = array_merge($this->json, $data);
			echo json_encode($this->json);
			// 别用 JSON_UNESCAPED_UNICODE 啊,Android 可能解不了
		}


		protected function getRunTime() {
			return microtime(true) - START_TIME;
		}

		protected function loadView($path, $data = array(), $return = false) {

			// --- 重构 loadView(string $path, boolen $return) ---
			if(is_array($data)) extract($data);
			else $return = $data;

			if($return) ob_start();

			require VIEW_PATH . $path . '.php';

			if($return) return ob_get_clean();

		}

		protected function mustHttps() {
			if ($this->isHttps()) {
				return true;
			} else {
				$redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . $redirect);
				return false;
			}
		}

		protected function isHttps() {
			if(!isset($_SERVER['HTTPS']))
				return false;
			if($_SERVER['HTTPS'] === 1) {  //Apache
				return true;
			} elseif($_SERVER['HTTPS'] === 'on'){ //IIS
				return true;
			} elseif($_SERVER['SERVER_PORT'] === 443){ //其他
				return true;
			}
			return false;
		}

	}

}

