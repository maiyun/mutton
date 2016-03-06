<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15-7-7
 * Time: 下午5:59
 */

namespace C\lib {

	class Mysql {

		private static $r = NULL;
		private static $w = NULL;

        private static $queries = [0 , 0];

		public static function isConnected($t = 'w') {
			if (self::$$t instanceof \mysqli) return true;
			return false;
		}

		public static function quit($t = 'w') {
            self::$$t = NULL;
		}

        public static function escape($str, $t = 'w') {
			return self::$$t->real_escape_string($str);
		}

        public static function query($sql, $t = 'w') {
            ++self::$queries[$t == 'w' ? 0 : 1];
			return self::$$t->query($sql);
		}

        public static function getError($t = 'w') {
			if (@isset(self::$$t->error))
				return self::$$t->error;
			else
				return self::$$t->connect_error;
		}

        public static function getErrno($t = 'w') {
			if (@isset(self::$$t->errno))
				return self::$$t->errno;
			else
				return self::$$t->connect_errno;
		}

        public static function connect($host = NULL, $user = NULL, $pwd = NULL, $dbName = NULL, $charset = NULL, $port = NULL, $t = 'w') {
            $host = $host ? $host : DB_HOST;
            $user = $user ? $user : DB_USERNAME;
            $pwd = $pwd ? $pwd : DB_PASSWORD;
            $dbName = $dbName ? $dbName : DB_DBNAME;
            $charset = $charset ? $charset : DB_CHARSET;
            $port = $port ? $port : DB_PORT;

			if (self::$$t = @new \mysqli($host, $user, $pwd, $dbName, $port)) {
				if (mysqli_connect_errno())
					return false;
				else {
                    self::$$t->set_charset($charset);
					return true;
				}
			} else
				return false;
		}

        public static function getInsertID($t = 'w') {
			return self::$$t->insert_id;
		}

        public static function getAffectRows($t = 'w') {
			return self::$$t->affected_rows;
		}

        function getQueries($t = 'w') {
            return self::$queries[$t == 'w' ? 0 : 1];
        }

	}

}

