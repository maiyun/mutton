<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/5/25
 * Time: 19:56
 */

namespace C\lib {

	/*
	 * 模式分为：1:db, 2:mem, 3:both
	 * 默认为 2
	 */

	class Session {

		public static $key = '';

		// --- 可编辑变量 ---

		public static $exp = 432000; // --- 5 天 ---
		public static $name = 'SESSIONKEY';
		public static $mode = 2;

		public static function update() {

			if(self::$mode == 1 || self::$mode == 3) {
				// --- DB ---
				$r = Db::prepare('UPDATE ' . DB_PRE . 'session SET data = :data, time_update = :time_update WHERE key = :key');
				$r->execute([
					':data' => serialize($_SESSION),
					':time_update' => $_SERVER['REQUEST_TIME'],
					':key' => self::$key
				]);
			}
			if(self::$mode == 2 || self::$mode == 3) {
				// --- MEM ---
				Memcached::set('__se_'.self::$key, $_SESSION, self::$exp >= 86400 ? 86400 : self::$exp);
			}

		}

		function gc() {

			if(self::$mode == 1 || self::$mode == 3)
				L()->Db->query('DELETE' . ' FROM ' . DB_PRE . 'session WHERE `time_update` < ' . Db::quote($_SERVER['REQUEST_TIME'] - self::$exp) . ';');

		}

		function start($name = NULL) {

			self::$name = $name ? $name : SESSKEY;

			if (!$this->long && $this->key == '') {
				if (isset($_POST[$this->cookie]) && $_POST[$this->cookie]) $this->key = $_POST[$this->cookie];
				else if (isset($_COOKIE[$this->cookie]) && $_COOKIE[$this->cookie]) $this->key = $_COOKIE[$this->cookie];
				if (!ctype_alnum($this->key)) $this->key = '';
			}

			// --- 初始化 Session 数组 ---
			$_SESSION = [];
			// --- 有 key 则查看 key 的信息是否存在
			$findOnDb = false;
			$needInsert = false;
			if ($this->key != '') {
				// --- 如果启用了内存加速则先在内存找 ---
				if ($this->mem) {
					$s = L()->Memcached->get('__sess_' . $this->key);
					// --- 内存没有，一会儿去数据库再找找 ---
					if ($s === false) {
						$findOnDb = true;
					} else {
						// --- 内存有直接读出 ---
						$_SESSION = $s;
					}
				} else {
					$findOnDb = true;
				}
				// --- 本来就该在数据库里找 ---
				// --- 在内存里没找到的也在数据库里找 ---
				if ($findOnDb) {
					$r = L()->Db->query('SELECT' . ' * FROM `' . L()->Db->pre . 'session` WHERE `key` = "' . $this->key . '";');
					// --- 影响 0 行代表之前没有 Session ---
					if (L()->Db->getAffectRows() == 0) {
						// --- 正常流程添加 Session ---
						$needInsert = true;
					} else {
						// --- 数据库里有 Session 直接读出 ---
						$s = $r->fetch_assoc();
						$_SESSION = unserialize($s['data']);
					}
				}
			} else {
				// --- 全新的机子 ---
				$needInsert = true;
			}
			// --- 本来就该添加个新 Session ---
			// --- 内存和数据库里没找到的也该添加个新 Session ---
			// --- 如果不存在不允许加新则返回错误 ---
			if ($needInsert) {
				$this->key = date('Ymd') . $this->random();
				$time = time();
				while (!L()->Db->query('INSERT' . ' INTO `' . L()->Db->pre . 'session` (`key`,`data`,`time`,`time_add`) VALUES ("' . $this->key . '","a:0:{}","' . $time . '","' . $time . '")', false))
					$this->key = date('Ymd') . $this->random();
				// --- 如果内存加速了则在页面结束时再写入内存 ---
			}
			if (!$this->long) if (!isset($_POST[$this->cookie])) setcookie($this->cookie, $this->key, time() + $this->exp, '/');
			return true;

		}

		protected function random()
		{
			$s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$sl = strlen($s);
			$t = '';
			for ($i = 8; $i; $i--)
				$t .= $s[rand(0, $sl - 1)];
			return $t;
		}

	}

}

