<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/7/4
 * Time: 22:48
 */

namespace C\lib {

// --- Memcached 为长连接进城池 ---

	class Memcached {

		/**
		 * @var \Memcached
		 */
		private static $link = NULL;

		public static function isConnect() {

			if (!count(self::$link->getServerList()))
				return false;
			else
				return true;

		}

		public static function connect($host = NULL, $user = NULL, $pwd = NULL, $pool = NULL, $port = NULL) {

			$host = $host ? $host : MC_HOST;
			$user = $user ? $user : MC_USERNAME;
			$pwd = $pwd ? $pwd : MC_PASSWORD;
			$port = $port ? $port : MC_PORT;
			$pool = $pool ? $pool : MC_POOL;

			if ($pool)
				self::$link = new \Memcached();
			else
				self::$link = new \Memcached($pool);

			self::$link->setOption(\Memcached::OPT_COMPRESSION, false);
			self::$link->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
			self::$link->addServer($host, $port);
			if (($user && $user != '') && ($pwd && $pwd != ''))
				self::$link->setSaslAuthData($user, $pwd);

		}

		public static function add($key, $val, $exp = 0) {

			return self::$link->add($key, $val, $exp);

		}

        public static function set($key, $val, $exp = 0) {

			self::$link->set($key, $val, $exp);

		}

        public static function get($key) {

			return self::$link->get($key);

		}

        public static function quit() {

			self::$link->quit();
			self::$link = NULL;

		}

        public static function delete($key) {

			self::$link->delete($key);

		}

        public static function getServerList() {

			if (self::$link !== NULL)
				return self::$link->getServerList();
			else return [];

		}

	}

}

