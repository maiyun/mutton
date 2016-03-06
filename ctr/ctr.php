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

	}

}

