<?php

namespace C {

	use C\lib\Aes;
	use C\lib\Session;

	class ctr {

		var $param = [];
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

		protected function writeAesJson($result, $data = []) {
			header('Content-type: application/json; charset=utf-8');
			$this->json['result'] = $result + 0;
			if($result <= 0) {
				if(is_array($data))
					$this->json = array_merge($this->json, $data);
				else
					$this->json['msg'] = $data;
			} else
				$this->json = array_merge($this->json, $data);
			$this->json['cookie'] = [
				'name' => Session::$cookie,
				'token' => Session::$token
			];
			if($aes = Aes::encrypt(json_encode($this->json), $_SESSION['aes_key'])) {
				echo json_encode([
					'result' => 1,
					'aes' => $aes
				]);
			} else
				echo json_encode([
					'result' => 0,
					'msg' => 'Aes 加密失败'
				]);

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

	}

}

