<?php
/*
CREATE TABLE `session` (
`id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `time_update` int(10) UNSIGNED NOT NULL,
  `time_add` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

ALTER TABLE `session`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
*/

/**
 * User: JianSuoQiYue
 * Date: 2015/05/25 19:56
 * Last: 22018-6-16 15:20
 */
declare(strict_types = 1);

namespace M\lib {

    require ETC_PATH.'session.php';

	/*
	 * 模式分为：1:db, 2:redis
	 * 默认为 2
	 */

	class Session {

	    /* @var $_redis Redis */
		private static $_redis = NULL;
        /* @var $_redis Db */
		private static $_db = NULL;
		/* @var $_sql Sql */
		private static $_sql = NULL;

		private static $_token = '';
		private static $_exp = 0;

        /**
         * @param Redis|Db $link
         * @param array $opt
         */
        public static function start($link, array $opt = []): void {

            $name = isset($opt['name']) ? $opt['name'] : SESSION_NAME;
            self::$_exp = isset($opt['exp']) ? $opt['exp'] : SESSION_EXP;

            if (isset($_POST[$name])) {
                self::$_token = $_POST[$name];
            } else if (isset($_COOKIE[$name])) {
                self::$_token = $_COOKIE[$name];
            }

            if ($link instanceof Redis) {
                self::$_redis = $link;
            } else {
                self::$_db = $link;
                self::$_sql = Sql::get('__session', isset($opt['pre']) ? $opt['pre'] : (SESSION_DB_PRE === NULL ? SQL_PRE : RDS_DB_PRE));
            }

            // --- 初始化 Session 数组 ---
            $_SESSION = [];
            $needInsert = false;
            // --- 有 token 则查看 token 的信息是否存在
            if (self::$_token != '') {
                // --- 如果启用了内存加速则在内存找 ---
                if (self::$_redis !== NULL) {
                    if(($data = self::$_redis->getValue('se_'.self::$_token)) === false) {
                        $needInsert = true;
                    } else {
                        $_SESSION = $data;
                    }
                } else {
                    // --- 数据库 ---
                    try {
                        self::$_sql->select('*', 'session')->where([
                            'token' => self::$_token
                        ]);
                        $ps = self::$_db->prepare(self::$_sql->getSql());
                        $ps->execute(self::$_sql->getData());
                        if ($data = $ps->fetch(\PDO::FETCH_ASSOC)) {
                            $_SESSION = unserialize($data['data']);
                        } else {
                            $needInsert = true;
                        }
                    } catch (\Exception $e) {
                        exit($e->getMessage());
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
                self::$_token = self::_random();
                if(self::$_redis !== NULL) {
                    while(!self::$_redis->setValue('se_'.self::$_token, [], self::$_exp, 'nx')) {
                        self::$_token = self::_random();
                    }
                } else {
                    self::$_sql->insert('session', [
                        'token' => self::$_token,
                        'data' => serialize([]),
                        'time_update' => $_SERVER['REQUEST_TIME'],
                        'time_add' => $_SERVER['REQUEST_TIME']
                    ]);
                    $ps = self::$_db->prepare(self::$_sql->getSql());
                    while(!$ps->execute(self::$_sql->getData())) {
                        self::$_token = self::_random();
                    }
                }
            }

            setcookie($name, self::$_token, $_SERVER['REQUEST_TIME'] + self::$_exp, '/');

            register_shutdown_function(function() {
                Session::update();
            });

        }

        /**
         * @throws \Exception
         */
		public static function update(): void {

			if(self::$_redis !== NULL) {
			    self::$_redis->setValue('se_' . self::$_token, $_SESSION, self::$_exp);
            } else {
			    self::$_sql->update('session', [
			        'data' => serialize($_SESSION),
                    'time_update' => $_SERVER['REQUEST_TIME']
                ])->where([
                    'token' => self::$_token
                ]);
			    $ps = self::$_db->prepare(self::$_sql->getSql());
				$ps->execute(self::$_sql->getData());
			}

		}

        /**
         * @throws \Exception
         */
		public static function gc(): void {

			if(self::$_redis === NULL) {
			    self::$_sql->delete('session')->where([
			        ['time_update', '<', $_SERVER['REQUEST_TIME'] - self::$_exp]
                ]);
                $ps = self::$_db->prepare(self::$_sql->getSql());
                $ps->execute(self::$_sql->getData());
            }

		}

		private static function _random(): string {
			$s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
			$sl = strlen($s);
			$t = '';
			for ($i = 8; $i; $i--)
				$t .= $s[rand(0, $sl - 1)];
			return date('Ymd').$t;
		}

	}

}

