<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2015/7/7 17:59
 * Last: 2018-12-8 22:39:24, 2020-01-03 17:25:04, 2020-2-17 23:26:01, 2023-8-25 15:40:29
 */

declare(strict_types = 1);

namespace lib;

use Exception;
use lib\Db\Stmt;
use PDO;
use PDOException;
use function sys\log;

require ETC_PATH . 'db.php';

class Db {

    /** @var int SQL 执行次数 */
    private $_queries = 0;

    /** @var PDO 原生连接对象 */
    private $_link;

    /**
     * @return Db
     */
    public static function get(): Db {
        return new Db();
    }

    /**
     * --- 判断是否创建了连接 ---
     * @return bool
     */
    public function isConnected(): bool {
        if ($this->_link instanceof PDO) {
            return true;
        }
        return false;
    }

    /**
     * --- 关闭连接 ---
     */
    public function quit(): void {
        $this->_link = null;
    }

    /**
     * --- 执行一个 query，有返回列表 ---
     * @param string $sql
     * @return false|Stmt
     */
    public function query(string $sql) {
        ++$this->_queries;
        $stmt = $this->_link->query($sql);
        if (!$stmt) {
            return false;
        }
        return new Stmt($stmt);
    }

    /**
     * --- 执行一个 exec，只返回影响行数 ---
     * @param string $sql
     * @return int|bool 小于 0 代表错误
     */
    public function exec(string $sql) {
        ++$this->_queries;
        try {
            return $this->_link->exec($sql);
        }
        catch (PDOException $e) {
            return false;
        }
    }

    /**
     * --- 返回错误信息 ---
     * @return array
     */
    public function getErrorInfo(): array {
        return $this->_link->errorInfo();
    }

    /**
     * --- 返回错误代码 ---
     * @return mixed
     */
    public function getErrorCode() {
        return $this->_link->errorCode();
    }

    /**
     * --- 连接数据库 ---
     * @param array $opt
     * @return bool|null
     */
    public function connect(array $opt = []) {
        try {
            $host = isset($opt['host']) ? $opt['host'] : MY_HOST;
            $port = isset($opt['port']) ? $opt['port'] : MY_PORT;
            $user = isset($opt['user']) ? $opt['user'] : MY_USER;
            $pwd = isset($opt['pwd']) ? $opt['pwd'] : MY_PWD;
            $name = isset($opt['name']) ? $opt['name'] : MY_NAME;
            $charset = isset($opt['charset']) ? $opt['charset'] : MY_CHARSET;

            if ($this->_link = new PDO('mysql:host=' . $host . '; port=' . $port . '; charset=' . $charset . '; dbname=' . $name, $user, $pwd)) {
                return true;
            }
            else {
                return false;
            }
            return null;
        }
        catch (Exception $exception) {
            log('[Db]' . $exception->getMessage(), '-error');
            return null;
        }
    }

    /**
     * --- 获取最后插入的 id ---
     * @return string
     */
    public function getInsertID(): string {
        return $this->_link->lastInsertId();
    }

    /**
     * --- 获取 SQL 执行次数 ---
     * @return int
     */
    public function getQueries(): int {
        return $this->_queries;
    }

    /**
     * --- 开启事务 ---
     * @return bool
     */
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
     * --- PDO 组装与绑定语句 ---
     * @param string $sql
     * @return Stmt|false
     */
    public function prepare(string $sql): Stmt | false {
        ++$this->_queries;
        $stmt = $this->_link->prepare($sql);
        if (!$stmt) {
            return false;
        }
        return new Stmt($stmt);
    }

}

