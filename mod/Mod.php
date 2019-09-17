<?php
/**
 * User: JianSuoQiYue
 * Date: 2015
 * Last: 2018-12-15 23:08:01, 2019-09-17
 */
declare(strict_types = 1);

namespace mod;

use lib\Db;
use lib\Sql;

/**
 * Class Mod
 * @package mod
 * --- 开启软更需要在表添加字段：ALTER TABLE `table_name` ADD `time_remove` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `xxx`; ---
 */
class Mod {

    // --- 可继承 ---
    protected static $_table = '';
    protected static $_primary = '';
    /** @var string 设置后将由 _keyGenerator 函数生成唯一字段 */
    protected static $_key = '';
    /** @var bool 可开启软删软更新软新增 */
    protected static $_soft = false;

    /** @var array 要 update 的内容 */
    protected $_updates = [];
    /** @var array 模型获取的属性 */
    protected $_data = [];

    /* @var Db $_conn 数据库连接对象 */
    protected $_conn = NULL;
    /** @var array etc 配置项，其实就是 Sql 对象的配置项 */
    protected $_etc = NULL;

    // --- 最后一次执行的 SQL 内容 ---
    protected $_lastSqlString = '';
    protected $_lastSqlData = [];

    // --- Mutton 独有配置项 [ 开始 ] ---

    /* @var Db $__conn Mutton 独有，设置映射静态数据库对象 */
    protected static $__conn = NULL;
    /** @var array Mutton 独有，设置 Sql 库的 etc 配置 */
    protected static $__etc = NULL;

    // --- Mutton 独有配置项 [ 结束 ] ---

    /**
     * 构造函数，etc 选项可选
     * @param array|NULL $row
     */
    public function __construct(array $row = NULL) {
        // --- sql 对象配置 ---
        if (Mod::$__etc !== NULL) {
            $this->_etc = Mod::$__etc;
        }
        // --- 导入数据库连接 ---
        $this->_conn = Mod::$__conn;
        // --- 第三个参数用于内部数据导入，将 data 数据合并到本实例化类 ---
        if ($row) {
            foreach ($row as $k => $v) {
                $this->_data[$k] = $v;
                $this->$k = $v;
            }
        }
    }

    // --- Mutton 独有函数 [ 开始 ] ---

    /**
     * --- 传入数据库对象 ---
     * @param Db $conn
     */
    public static function setConn(Db $conn) {
        self::$__conn = $conn;
    }

    /**
     * --- 传入 Sql 所需配置项 ---
     * @param array $etc
     */
    public static function setEtc(array $etc) {
        self::$__etc = $etc;
    }

    /**
     * --- 开启数据库事务 ---
     */
    public static function beginTransaction() {
        self::$__conn->beginTransaction();
    }
    public static function commit() {
        self::$__conn->commit();
    }
    public static function rollBack() {
        self::$__conn->rollBack();
    }

    // --- Mutton 独有函数 [ 结束 ] ---

    /**
     * --- 获取创建对象，通常用于新建数据库条目 ---
     * @return Mod
     */
    public static function getCreate() {
        return new static();
    }

    /**
     * --- 设置模型属性 ---
     * @param string|array $n
     * @param string|int|float $v 可能是数字
     */
    public function set($n, $v = ''): void {
        if(!is_string($n)) {
            foreach ($n as $k => $v) {
                // --- 强制更新，因为有的可能就是要强制更新既然设置了 ---
                $this->_updates[$k] = true;
                $this->_data[$k] = $v;
                $this->$k = $v;
            }
        } else {
            $this->_updates[$n] = true;
            $this->_data[$n] = $v;
            $this->$n = $v;
        }
    }

    /**
     * --- 获取一个字段值 ---
     * @param string $n 字段名
     * @return mixed
     */
    public function getVal(string $n) {
        return $this->_data[$n];
    }

    /**
     * --- 更新 set 的数据到数据库 ---
     * @return bool
     */
    public function update(): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->_data[$k];
        }
        if(count($updates) > 0) {
            $sql = Sql::get($this->_etc);
            $sql->update(static::$_table, $updates)->where([
                static::$_primary => $this->_data[static::$_primary]
            ]);

            $this->_lastSqlString = $sql->getSql();
            $this->_lastSqlData = $sql->getData();
            $ps = $this->_conn->prepare($sql->getSql());

            if ($ps->execute($sql->getData())) {
                $this->_updates = [];
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * --- 移除本条目 ---
     * @param boolean|null $raw
     * @return bool
     */
    public function remove($raw = NULL): bool {
        $sql = Sql::get($this->_etc);
        if (static::$_soft && ($raw !== true)) {
            $sql->update(static::$_table, [
                'time_remove' => $_SERVER['REQUEST_TIME']
            ])->where([
                static::$_primary => $this->_data[static::$_primary],
                'time_remove' => '0'
            ]);
        } else {
            $sql->delete(static::$_table)->where([
                static::$_primary => $this->_data[static::$_primary]
            ]);
        }

        $this->_lastSqlString = $sql->getSql();
        $this->_lastSqlData = $sql->getData();
        $ps = $this->_conn->prepare($sql->getSql());

        if ($ps->execute($sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /** @var int 创建后不重新获取 */
    const NORMAL = 0;
    /** @var int 创建后重新获取并锁定（针对事务） */
    const LOCK = 1;
    /** @var int 创建后仅重新获取不锁定 */
    const RELOAD = 2;

    /**
     * --- 创建条目 ---
     * @param int $type 创建的类型，表示创建后是否重新获取值，默认不获取
     * @return bool
     */
    public function create(int $type = 0): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->_data[$k];
        }

        $sql = Sql::get($this->_etc);

        if (static::$_key !== '') {
            do {
                $updates[static::$_key] = $this->_keyGenerator();
                $sql->insert(static::$_table, $updates);

                $this->_lastSqlString = $sql->getSql();
                $this->_lastSqlData = $sql->getData();

                $ps = $this->_conn->prepare($sql->getSql());
            } while (!$ps->execute($sql->getData()) && ($this->_conn->getErrorCode() == 1062));
        } else {
            $sql->insert(static::$_table, $updates);

            $this->_lastSqlString = $sql->getSql();
            $this->_lastSqlData = $sql->getData();

            $ps = $this->_conn->prepare($sql->getSql());
            if (!$ps->execute($sql->getData())) {
                return false;
            }
        }
        if ($ps->rowCount() > 0) {
            $this->_updates = [];
            $this->_data[static::$_primary] = $this->_conn->getInsertID();
            $this->{static::$_primary} = $this->_conn->getInsertID();
            // --- 重新获取 ---
            if ($type === 1 || $type === 2) {
                $sql->select('*', static::$_table)->where([
                    static::$_primary => $this->_data[static::$_primary]
                ]);
                if ($type === 1) {
                    $sql->lock();
                }
                $ps = $this->_conn->prepare($sql->getSql());
                $ps->execute($sql->getData());
                $row = $ps->fetch(\PDO::FETCH_ASSOC);
                foreach ($row as $k => $v) {
                    $this->_data[$k] = $v;
                    $this->$k = $v;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 获取最后执行的 SQL 字串 ---
     * @return string
     */
    public function getLastSqlString(): string {
        return $this->_lastSqlString;
    }

    /**
     * --- 获取最后执行的 Data 数据 ---
     * @return array
     */
    public function getLastSqlData(): array {
        return $this->_lastSqlData;
    }

    /**
     * --- 获取最后一次完整的 SQL 字符串 ---
     * @return string
     */
    public function getLastSqlFormat(): string {
        return Sql::sFormat($this->_lastSqlString, $this->_lastSqlData);
    }

    public function __setLastSqlString(string $sql): void {
        $this->_lastSqlString = $sql;
    }
    public function __setLastSqlData(array $data): void {
        $this->_lastSqlData = $data;
    }

    /**
     * --- 当 _key 不为空时，则依据继承此方法的方法自动生成填充 key ---
     * @return string
     */
    protected function _keyGenerator(): string {
        return '';
    }

    /**
     * --- 获取值对象 ---
     * @return array
     */
    public function toArray(): array {
        return $this->_data;
    }

    // --- 以下为静态方法 ---

    /**
     * @param array|string $where
     * @param array $opt lock: boolean, raw: boolean
     * @return Mod|null
     */
    public static function get($where, array $opt = []) {
        // $mod = static::class;
        $sql = Sql::get(Mod::$__etc);
        $sql->select('*', static::$_table);
        // --- 判断是否筛掉已删除的 ---
        if (static::$_soft && (!isset($opt['raw']) || $opt['raw'] !== true)) {
            $sql->where([
                'time_remove' => '0'
            ]);
        }
        if (is_string($where)) {
            $sql->append(' WHERE ' . $where);
        } else {
            $sql->where($where);
        }
        if (isset($opt['lock']) && $opt['lock']) {
            $sql->lock();
        }
        $ps = self::$__conn->prepare($sql->getSql());
        if ($ps->execute($sql->getData())) {
            if ($row = $ps->fetch(\PDO::FETCH_ASSOC)) {
                return new static($row);
            } else {
                return NULL;
            }
        } else {
            return NULL;
        }
    }

    /**
     * --- 添加一个序列 ---
     * @param array $cs 字段列表
     * @param array $vs 参数列表
     * @return bool
     */
    public static function insert(array $cs, array $vs): bool {
        $sql = Sql::get(Mod::$__etc);
        $sql->insert(static::$_table, $cs, $vs);
        $ps = self::$__conn->prepare($sql->getSql());
        if ($ps->execute($sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 获取添加一个序列的模拟 SQL ---
     * @param array $cs 字段列表
     * @param array $vs 参数列表
     * @return string
     */
    public static function insertSql(array $cs, array $vs = []): string {
        $sql = Sql::get(Mod::$__etc);
        $sql->insert(static::$_table, $cs, $vs);
        return $sql->format();
    }

    /**
     * --- 获取列表 ---
     * @param array $opt where, limit, by, group, key, lock, select, raw
     * @return array
     */
    public static function getList(array $opt = []): array {
        $sql = Sql::get(Mod::$__etc);
        $sql->select(isset($opt['select']) ? $opt['select'] : '*', static::$_table);
        if (isset($opt['where'])) {
            if (is_string($opt['where'])) {
                $sql->append(' WHERE (' . $opt['where'] . ')');
                if (static::$_soft && (!isset($opt['raw']) || $opt['raw'] !== true)) {
                    $sql->append(' AND `time_remove` = 0');
                }
            } else {
                if (!isset($opt['raw']) || $opt['raw'] !== true) {
                    $opt['where']['time_remove'] = '0';
                }
                $sql->where($opt['where']);
            }
        }
        if(isset($opt['group'])) {
            $sql->group($opt['group']);
        }
        if(isset($opt['by'])) {
            $sql->by($opt['by'][0], isset($opt['by'][1]) ? $opt['by'][1] : 'DESC');
        }
        $total = 0;
        if(isset($opt['limit'])) {
            if(isset($opt['limit'][2])) {
                // --- 分页 ---
                $sstr = preg_replace('/SELECT .+? FROM/', 'SELECT COUNT(0) AS count FROM', $sql->getSql());
                $ps = self::$__conn->prepare($sstr);
                $ps->execute($sql->getData());
                $row = $ps->fetch(\PDO::FETCH_ASSOC);
                $total = $row['count'] + 0;
                // --- 计算完整 ---
                $sql->limit($opt['limit'][1] * ($opt['limit'][2] - 1), $opt['limit'][1]);
            } else {
                $sql->limit($opt['limit'][0], $opt['limit'][1]);
            }
        }
        if (isset($opt['lock']) && $opt['lock']) {
            $sql->lock();
        }

        // --- 执行查询 ---
        $ps = self::$__conn->prepare($sql->getSql());
        $ps->execute($sql->getData());

        $list = [];
        while ($row = $ps->fetch(\PDO::FETCH_ASSOC)) {
            $obj = new static($row);
            $obj->__setLastSqlString($sql->getSql());
            $obj->__setLastSqlData($sql->getData());
            if (isset($opt['key'])) {
                $list[$row[$opt['key']]] = $obj;
            } else {
                $list[] = $obj;
            }

        }
        // --- 返回 ---
        return [
            'total' => $total,
            'list' => $list
        ];
    }

    /**
     * --- 根据条件计算条数 ---
     * @param array $opt where, lock, select, raw
     * @return object
     */
    public static function count($opt = []) {
        $sql = Sql::get(Mod::$__etc);
        $sql->select(isset($opt['select']) ? $opt['select'] : 'COUNT(0) AS count', static::$_table);
        if (isset($opt['where'])) {
            if (is_string($opt['where'])) {
                $sql->append(' WHERE (' . $opt['where'] . ')');
                if (static::$_soft && (!isset($opt['raw']) || $opt['raw'] !== true)) {
                    $sql->append(' AND `time_remove` = 0');
                }
            } else {
                if (static::$_soft && (!isset($opt['raw']) || $opt['raw'] !== true)) {
                    $opt['where']['time_remove'] = '0';
                }
                $sql->where($opt['where']);
            }
        } else {
            if (static::$_soft && (!isset($opt['raw']) || $opt['raw'] !== true)) {
                $sql->where([
                    'time_remove' => '0'
                ]);
            }
        }
        // --- 是否锁定 ---
        if (isset($opt['lock']) && $opt['lock']) {
            $sql->lock();
        }
        // --- 开始 ---
        $ps = self::$__conn->prepare($sql->getSql());
        $ps->execute($sql->getData());
        return $ps->fetchObject();
    }

    /**
     * --- 根据条件移除条目 ---
     * @param string|array $where 筛选条件
     * @param bool $raw 是否真实
     * @return bool
     */
    public static function removeByWhere($where, ?bool $raw = NULL): bool {
        $sql = Sql::get(Mod::$__etc);
        if (static::$_soft && ($raw !== true)) {
            // --- 软删除 ---
            $sql->update(static::$_table, [
                'time_remove' => $_SERVER['REQUEST_TIME']
            ]);
            if (is_string($where)) {
                $sql->append(' WHERE ('.$where.') AND `time_remove` = 0');
            } else {
                $where['time_remove'] = '0';
                $sql->where($where);
            }
        } else {
            // --- 真删除 ---
            $sql->delete(static::$_table);
            if (is_string($where)) {
                $sql->append(' WHERE ' . $where);
            } else {
                $sql->where($where);
            }
        }
        $ps = self::$__conn->prepare($sql->getSql());
        if ($ps->execute($sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 根据条件更新数据 ---
     * @param array $data 要更新的数据
     * @param array|string $where 筛选条件
     * @param bool $raw 是否真实
     * @return bool
     */
    public static function updateByWhere(array $data, $where, bool $raw = NULL): bool {
        $sql = Sql::get(Mod::$__etc);
        $sql->update(static::$_table, $data);
        if (is_string($where)) {
            $sql->append(' WHERE ('.$where.')');
            if (static::$_soft && (!isset($opt['raw']) || $raw !== true)) {
                $sql->append(' AND `time_remove` = 0');
            }
        } else {
            if (static::$_soft && (!isset($opt['raw']) || $raw !== true)) {
                $where['time_remove'] = '0';
            }
            $sql->where($where);
        }
        $ps = self::$__conn->prepare($sql->getSql());
        if ($ps->execute($sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

}

