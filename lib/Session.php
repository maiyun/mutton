<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/5/25
 * Time: 19:56
 */

namespace C\lib {

	/*
	 * 模式分为：1:db, 2:mem
	 * 默认为 2
	 */

	class Session {

		public static $token = '';

		// --- 可编辑变量 ---

		public static $exp = 1209600; // --- 14 天 ---
		public static $cookie = NULL;
		public static $useMem = NULL;

		public static function update() {

			if(self::$useMem)
				Redis::set('se_'.self::$token, $_SESSION, self::$exp);
			else {
				$r = Db::prepare('UPDATE ' . DB_PRE . 'session SET data = :data, time_update = :time_update WHERE token = :token');
				$r->execute([
					':data' => serialize($_SESSION),
					':time_update' => $_SERVER['REQUEST_TIME'],
					':token' => self::$token
				]);
			}

		}

		public static function gc() {

			if(!self::$useMem)
				Db::exec('DELETE' . ' FROM ' . DB_PRE . 'session WHERE `time_update` < ' . ($_SERVER['REQUEST_TIME'] - self::$exp));

		}

		public static function start($cookie = NULL) {

			self::$cookie = $cookie ? $cookie : SESSION_NAME;
			self::$useMem = SESSION_MEM;

			if (isset($_POST[self::$cookie]) && $_POST[self::$cookie]) self::$token = $_POST[self::$cookie];
			else if (isset($_COOKIE[self::$cookie]) && $_COOKIE[self::$cookie]) self::$token = $_COOKIE[self::$cookie];

			// --- 判断 Redis 是否已经连接 ---
			if(self::$useMem)
				if(!Redis::isConnect()) Redis::connect();
			// --- 初始化 Session 数组 ---
			$_SESSION = [];
			$needInsert = false;
			// --- 有 token 则查看 token 的信息是否存在
			if (self::$token != '') {
				// --- 如果启用了内存加速则在内存找 ---
				if (self::$useMem) {
					if(($data = Redis::get('se_'.self::$token)) === false)
						$needInsert = true;
					else
						$_SESSION = $data;
				// --- 在数据库找 ---
				} else {
					$p = Db::prepare('SELECT' . ' * FROM ' . DB_PRE . 'session WHERE token = :token', 'r');
					$p->execute([
						':token' => self::$token
					]);
					if($data = $p->fetch(\PDO::FETCH_ASSOC)) {
						$_SESSION = unserialize($data['data']);
					} else
						$needInsert = true;
				}
			} else {
				// --- 全新的机子 ---
				$needInsert = true;
			}
			// --- 本来就该添加个新 Session ---
			// --- 内存和数据库里没找到的也该添加个新 Session ---
			// --- 如果不存在不允许加新则返回错误 ---
			if ($needInsert) {
				self::$token = self::random();
				if(self::$useMem) {
					while(!Redis::set('se_'.self::$token, [], self::$exp, 'nx'))
						self::$token = self::random();
				} else {
					$p = Db::prepare('INSERT' . ' INTO ' . DB_PRE . 'session (token,data,time_update,time_add) VALUES (:token,:data,:time_update,:time_add)');
					while(!$p->execute([
						':token' => self::$token,
						':data' => serialize([]),
						':time_update' => $_SERVER['REQUEST_TIME'],
						':time_add' => $_SERVER['REQUEST_TIME']
					]))
						self::$token = self::random();
				}
			}

			setcookie(self::$cookie, self::$token, $_SERVER['REQUEST_TIME'] + self::$exp, '/');

			register_shutdown_function(function() {
				Session::update();
			});

			return true;

		}

		protected static function random() {
			$s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$sl = strlen($s);
			$t = '';
			for ($i = 8; $i; $i--)
				$t .= $s[rand(0, $sl - 1)];
			return date('Ymd').$t;
		}

	}

}

