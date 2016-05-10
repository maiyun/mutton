<?php
/**
 * Created by PhpStorm.
 * User: yunbo
 * Date: 2015/10/26
 * Time: 14:23
 */

namespace C\lib {

	class Net {

		public static function get($url) {

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$output = curl_exec($ch);
			curl_close($ch);
			if ($output) return $output;
			else return false;

		}

		public static function post($url, $data = []) {

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			$output = curl_exec($ch);
			curl_close($ch);
			if ($output) return $output;
			else return false;

		}

	}

}

