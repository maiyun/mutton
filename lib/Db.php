<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/7/7 17:59
 * Last: 2018/06/11 16:48
 */
declare(strict_types = 1);

namespace M\lib {

    require ETC_PATH.'db.php';

	class Db {

	    private static $_poll = [];

        private $_queries = 0; // --- query 次数 ---
		private $_executions = 0; // --- exec 次数 ---
		private $_affectRows = 0; // --- 影响行数 ---
        private $_opt = []; // --- 连接数据 ---

        /* @var $link \PDO */
        private $_link;

        /**
         * --- 获取数据库连接对象 ---
         * @param string $name
         * @param array $opt
         * @return Db
         * @throws \Exception
         */
        public static function get(string $name = 'main', array $opt = []): Db {
            if (isset(self::$_poll[$name])) {
                return self::$_poll[$name];
            } else {
                $db = new Db();
                try {
                    $db->connect($opt);
                    self::$_poll[$name] = $db;
                    return self::$_poll[$name];
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }

        // --- 判断 poll 中是否已经连接 ---
        public static function checkPool(string $name): bool {
            if (isset(self::$_poll[$name])) {
                return true;
            } else {
                return false;
            }
        }

        // --- 判断是否创建了链接 ---
		public function isConnected(): bool {
			if ($this->_link instanceof \PDO) {
			    return true;
            }
			return false;
		}

		// --- 关闭连接 ---
		public function quit(): void {
            $this->_link = NULL;
		}

		// --- 转义 ---
        public function quote(string $str): string {
			return $this->_link->quote($str);
		}

        /**
         * 执行一个 query，有返回列表
         * @param string $sql
         * @return \PDOStatement
         */
        public function query(string $sql): \PDOStatement {
            ++$this->_queries;
			return $this->query($sql);
		}

		/**
         * 执行一个 exec，只返回影响行数
		 * @param string $sql
		 * @return int
		 */
		public function exec(string $sql): int {
			++$this->_executions;
			return $this->_affectRows = $this->exec($sql);
		}

		// --- 返回错误信息 ---
        public function getErrorInfo(): array {
			return $this->_link->errorInfo();
		}

		// --- 返回错误代码 ---
        public function getErrorCode(): string {
			return $this->_link->errorCode();
		}

		// --- 连接数据库 ---

        /**
         * @param array $opt
         * @return bool
         * @throws \Exception
         */
        public function connect(array $opt = []): bool {
            $host = isset($opt['host']) ? $opt['host'] : DB_HOST;
            $user = isset($opt['user']) ? $opt['user'] : DB_USERNAME;
            $pwd = isset($opt['pwd']) ? $opt['pwd'] : DB_PASSWORD;
            $name = isset($opt['name']) ? $opt['name'] : DB_NAME;
            $charset = isset($opt['charset']) ? $opt['charset'] : DB_CHARSET;
            $port = isset($opt['port']) ? $opt['port'] : DB_PORT;

            $this->_opt = [
                'host' => $host,
                'user' => $user,
                'pwd' => $pwd,
                'name' => $name,
                'charset' => $charset,
                'port' => $port
            ];

            try {
                if ($this->_link = new \PDO('mysql:host=' . $host . '; port=' . $port . '; charset=' . $charset . '; dbname=' . $name, $user, $pwd)) {
                    return true;
                } else {
                    return false;
                }
            } catch (\Exception $exception) {
                throw new \Exception('[Error] Can not connect to MySQL server on '.$host.'.');
            }
		}

		// --- 获取连接数据 ---
        public function getOpt(string $name) {
		    return isset($this->_opt[$name]) ? $this->_opt[$name] : '';
        }

		// --- 获取最后插入的 id ---
        public function getInsertID(): string {
			return $this->_link->lastInsertId();
		}

        public function getAffectRows(): int {
		    return $this->_affectRows;
		}

        public function getQueries(): int {
            return $this->_queries;
        }

		public function getExecutions(): int {
			return $this->_executions;
		}

		// --- 事物操作 ---
		public function beginTransaction(): bool {
		    return $this->_link->beginTransaction();
		}
		public function commit(): bool {
			return $this->_link->commit();
		}
		public function rollBack() {
			return $this->_link->rollBack();
		}

		/**
         * PDO 组装与绑定
		 * @param string $sql
		 * @return \PDOStatement
		 */
		public function prepare(string $sql): \PDOStatement {
		    return $this->_link->prepare($sql);
		}

	}

}

