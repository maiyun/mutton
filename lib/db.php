<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.1","folder":false} - END
 * Date: 2015/7/7 17:59
 * Last: 2018-12-8 22:39:24, 2020-01-03 17:25:04, 2020-2-17 23:26:01
 */
declare(strict_types = 1);

namespace lib;

use Exception;
use PDO;
use PDOException;
use PDOStatement;
use function sys\log;

require ETC_PATH.'db.php';

class Db {

    // --- 核心类型 ---
    const MYSQL = 'mysql';
    const SQLITE = 'sqlite';

    /** @var int SQL 执行次数 */
    private $_queries = 0;

    /* @var PDO */
    private $_link;
    /** @var string 当前核心 */
    private $_core = '';

    public function __construct(string $core) {
        $this->_core = $core;
    }

    /**
     * @param string $core
     * @return Db
     */
    public static function get(string $core = self::MYSQL): Db {
        return new Db($core);
    }

    /**
     * --- 获取当前核心是什么 ---
     * @return string
     */
    public function getCore(): string {
        return $this->_core;
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
     * --- 转义 ---
     * @param string $str
     * @return string
     */
    public function quote(string $str): string {
        return $this->_link->quote($str);
    }

    /**
     * --- 执行一个 query，有返回列表 ---
     * @param string $sql
     * @return false|PDOStatement
     */
    public function query(string $sql) {
        ++$this->_queries;
        try {
            return $this->_link->query($sql);
        }
        catch (PDOException $e) {
            return false;
        }
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
            switch ($this->_core) {
                case 'mysql': {
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
                    break;
                }
                case 'sqlite': {
                    $path = isset($opt['path']) ? $opt['path'] : SL_PATH;

                    if ($this->_link = new PDO('sqlite:' . $path)) {
                        return true;
                    }
                    else {
                        return false;
                    }
                    break;
                }
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
     * @return PDOStatement
     */
    public function prepare(string $sql): PDOStatement {
        ++$this->_queries;
        return $this->_link->prepare($sql);
    }

}

