<?php

namespace C {

	class ctr {

		var $param = [];
		var $json = ['result' => '1'];

		protected function writeJson($result, $data) {
			header('Content-type: application/json; charset=utf-8');
			$this->json['result'] = $result + 0;
			if($result <= 0)
				$this->json['msg'] = $data;
			else
				$this->json = array_merge($this->json, $data);
			echo json_encode($this->json, JSON_UNESCAPED_UNICODE);
		}

		protected function getRunTime() {
			return microtime(true) - START_TIME;
		}

		function loadView($path, $data = array(), $return = false) {

			// --- 重构 loadView(string $path, boolen $return) ---
			if(is_array($data)) extract($data);
			else $return = $data;

			if($return) ob_start();

			require VIEW_PATH . $path . '.php';

			if($return) return ob_get_clean();

		}

	}

}

