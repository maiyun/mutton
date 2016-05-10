<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 15-7-7
 * Time: 下午5:59
 */

namespace C\lib {

	class Db {

		private static $r = NULL;
		/**
		 * @var \PDO
		 */
		private static $w = NULL;

        private static $queries = [0 , 0];
		private static $executions = [0 , 0];
		private static $affectRows = [0, 0];

		public static function isConnected($t = 'w') {
			if (self::$$t instanceof \PDO) return true;
			return false;
		}

		public static function quit($t = 'w') {
            self::$$t = NULL;
		}

        public static function quote($str) {
			return self::$w ? self::$w->quote($str) : self::$r->quote($str);
		}

		/**
		 * @param string $sql
		 * @param string $t
		 * @return \PDOStatement
		 */
        public static function query($sql, $t = 'w') {
			if($t == 'r' && self::$r === NULL) $t = 'w';
            ++self::$queries[$t == 'w' ? 0 : 1];
			return self::$$t->query($sql);
		}

		/**
		 * @param string $sql
		 * @param string $t
		 * @return int
		 */
		public static function exec($sql, $t = 'w') {
			++self::$executions[$t == 'w' ? 0 : 1];
			return self::$affectRows[$t == 'w' ? 0 : 1] = self::$$t->exec ($sql);
		}

        public static function getErrorInfo($t = 'w') {
			return self::$$t->errorInfo();
		}

        public static function getErrorCode($t = 'w') {
			return self::$$t->errorCode();
		}

        public static function connect($host = NULL, $user = NULL, $pwd = NULL, $dbName = NULL, $charset = NULL, $port = NULL, $t = 'w') {
            $host = $host ? $host : DB_HOST;
            $user = $user ? $user : DB_USERNAME;
            $pwd = $pwd ? $pwd : DB_PASSWORD;
            $dbName = $dbName ? $dbName : DB_DBNAME;
            $charset = $charset ? $charset : DB_CHARSET;
            $port = $port ? $port : DB_PORT;

			if(self::$$t = new \PDO('mysql:host='.$host.'; port='.$port.'; charset='.$charset.'; dbname='.$dbName, $user, $pwd)) {
				return true;
			} else
				return false;
		}

        public static function getInsertID($t = 'w') {
			return self::$$t->lastInsertId();
		}

        public static function getAffectRows($t = 'w') {
			if($t == 'r' && self::$r === NULL) $t = 'w';
			return self::$affectRows[$t == 'w' ? 0 : 1];
		}

        public static function getQueries($t = 'w') {
            return self::$queries[$t == 'w' ? 0 : 1];
        }

		public static function getExecutions($t = 'w') {
			return self::$executions[$t == 'w' ? 0 : 1];
		}
		
		public static function beginTransaction($t = 'w') {
			return self::$$t->beginTransaction();
		}
		
		public static function commit($t = 'w') {
			return self::$$t->commit();
		}

		public static function rollBack($t = 'w') {
			return self::$$t->rollBack();
		}

		/**
		 * @param string $sql
		 * @param string $t
		 * @return \PDOStatement
		 */
		public static function prepare($sql, $t = 'w') {
			if($t == 'r' && self::$r === NULL) $t = 'w';
			return self::$$t->prepare($sql);
		}
		
		public static function bindPrepare($arr, $split = ', ') {
			$rtn = ['sql' => '', 'arr' => []];
			foreach ($arr as $key => $val) {
				$rtn['sql'] .= $key . ' = :' . $key . $split;
				$rtn['arr'][':'.$key] = $val;
			}
			$rtn['sql'] = substr($rtn['sql'], 0, -strlen($split));
			return $rtn;
		}

	}

}

