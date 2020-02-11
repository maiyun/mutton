<?php
/**
 * User: JianSuoQiYue
 * Date: 2015
 * Last: 2018-12-15 23:08:01, 2019-10-2
 */
declare(strict_types = 1);

namespace mod;

use Generator;
use lib\Db;
use lib\LSql;
use lib\Sql;
use PDO;

/**
 * Class Mod
 * @package mod
 * --- 开启软更需要在表添加字段：ALTER TABLE `table_name` ADD `time_remove` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `xxx`; ---
 */
class Mod {

    /** @var string 表名 */
    protected static $_table = '';
    /** @var string 主键字段名 */
    protected static $_primary = 'id';
    /** @var string 设置后将由 _keyGenerator 函数生成唯一字段 */
    protected static $_key = '';
    /** @var bool 可开启软删软更新软新增 */
    protected static $_soft = false;

    /** @var array 要 update 的内容 */
    protected $_updates = [];
    /** @var array 模型获取的属性 */
    protected $_data = [];

    /* @var Db $_db 数据库连接对象 */
    protected $_db = null;

    // --- 系统自用 ---

    /** @var LSql $_sql Sql 库对象 */
    protected $_sql = null;

    // --- Mutton PHP 框架配置项 [ 开始 ] ---

    /* @var Db $__db 设置映射静态数据库对象 */
    protected static $__db = null;
    /** @var string|null Mutton 独有，设置 Sql 库的 pre 配置 */
    protected static $__pre = null;

    // --- Mutton PHP 框架配置项 [ 结束 ] ---

    /**
     * 构造函数，etc 选项可选
     * @param array $opt
     */
    public function __construct(array $opt = []) {
        // --- 导入数据库连接 ---
        $this->_db = Mod::$__db;
        // --- 新建 sql 对象 ---
        $this->_sql = Sql::get(Mod::$__pre);
        // --- 第三个参数用于内部数据导入，将 data 数据合并到本实例化类 ---
        if (isset($opt['row'])) {
            foreach ($opt['row'] as $k => $v) {
                $this->_data[$k] = $v;
                $this->$k = $v;
            }
        }
        if (isset($opt['select'])) {
            $this->_sql->select($opt['select'], static::$_table);
        }
        if (isset($opt['where'])) {
            $this->_sql->select('*', static::$_table);
            if (is_string($opt['where'])) {
                // --- 判断是否筛掉已删除的 ---
                $this->_sql->append(' WHERE (' . $opt['where'] . ')');
                if (static::$_soft && (!isset($opt['raw']) || $opt['raw'] === false)) {
                    $this->_sql->append(' AND `time_remove` = 0');
                }
            } else {
                if (static::$_soft && (!isset($opt['raw']) || $opt['raw'] === false)) {
                    $opt['where']['time_remove'] = '0';
                }
                $this->_sql->where($opt['where']);
            }
        }
    }

    // --- Mutton PHP 框架函数 [ 开始 ] ---

    /**
     * --- 传入数据库对象 ---
     * @param Db $db
     */
    public static function setDb(Db $db) {
        self::$__db = $db;
    }

    /**
     * --- 传入 Sql 所需 pre ---
     * @param string $pre
     */
    public static function setPre(string $pre) {
        self::$__pre = $pre;
    }

    /**
     * --- 开启数据库事务 ---
     */
    public static function beginTransaction() {
        self::$__db->beginTransaction();
    }
    public static function commit() {
        self::$__db->commit();
    }
    public static function rollBack() {
        self::$__db->rollBack();
    }

    // --- Mutton 框架函数 [ 结束 ] ---

    // --- 静态方法 ---

    /**
     * --- 添加一个序列 ---
     * @param array $cs 字段列表
     * @param array $vs 数据列表
     * @return bool
     */
    public static function insert(array $cs, array $vs = []): bool {
        $sql = Sql::get(Mod::$__pre);
        $sql->insert(static::$_table)->values($cs, $vs);
        $ps = self::$__db->prepare($sql->getSql());
        if ($ps->execute($sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 获取添加一个序列的模拟 SQL ---
     * @param array $cs 字段列表
     * @param array $vs 数据列表
     * @return string
     */
    public static function insertSql(array $cs, array $vs = []): string {
        $sql = Sql::get(Mod::$__pre);
        $sql->insert(static::$_table)->values($cs, $vs);
        return $sql->format();
    }

    /**
     * --- 插入数据如果唯一键冲突则更新 ---
     * @param array $data 要插入的数据
     * @param array $update 要更新的数据
     * @return bool
     */
    public static function insertDuplicate(array $data, array $update) {
        $sql = Sql::get(Mod::$__pre);
        $sql->insert(static::$_table)->values($data)->duplicate($update);
        $ps = self::$__db->prepare($sql->getSql());
        if ($ps->execute($sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 根据条件移除条目 ---
     * @param string|array $where 筛选条件
     * @param bool $raw 是否真实
     * @return bool
     */
    public static function removeByWhere($where, ?bool $raw = false): bool {
        $sql = Sql::get(Mod::$__pre);
        if (static::$_soft && ($raw === false)) {
            // --- 软删除 ---
            $sql->update(static::$_table, [
                'time_remove' => time()
            ]);
            if (is_string($where)) {
                $sql->append(' WHERE (' . $where . ') AND `time_remove` = 0');
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
        $ps = self::$__db->prepare($sql->getSql());
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
    public static function updateByWhere(array $data, $where, bool $raw = false): bool {
        $sql = Sql::get(Mod::$__pre);
        $sql->update(static::$_table, $data);
        if (is_string($where)) {
            $sql->append(' WHERE (' . $where . ')');
            if (static::$_soft && (!isset($opt['raw']) || $raw === false)) {
                $sql->append(' AND `time_remove` = 0');
            }
        } else {
            if (static::$_soft && (!isset($opt['raw']) || $raw === false)) {
                $where['time_remove'] = '0';
            }
            $sql->where($where);
        }
        $ps = self::$__db->prepare($sql->getSql());
        if ($ps->execute($sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- select 自定字段 ---
     * @param string|string[] $c 字段字符串或字段数组
     * @return static
     */
    public static function select($c) {
        return new static([
            'select' => $c
        ]);
    }

    /**
     * --- 通过 where 条件获取模型 ---
     * @param array $s
     * @param bool $raw
     * @return static
     */
    public static function where(array $s, $raw = false) {
        return new static([
            'where' => $s,
            'raw' => $raw
        ]);
    }

    /**
     * --- 获取创建对象，通常用于新建数据库条目 ---
     * @return static
     */
    public static function getCreate() {
        return new static();
    }

    /**
     * --- 根据主键获取对象 ---
     * @param string|int|float $val 主键值
     * @param bool $raw
     * @return bool|Mod|null
     */
    public static function find($val, $raw = false) {
        return (new static([
            'where' => [
                static::$_primary => $val
            ],
            'raw' => $raw
        ]))->first();
    }

    // --- 动态方法 ---

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
    public function get(string $n) {
        return $this->_data[$n];
    }

    /**
     * --- 创建数据 ---
     * @return bool
     */
    public function create(): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->_data[$k];
        }

        if (static::$_key !== '' && !isset($updates[static::$_key])) {
            do {
                $updates[static::$_key] = $this->_keyGenerator();
                $this->_sql->insert(static::$_table)->values($updates);
                $ps = $this->_db->prepare($this->_sql->getSql());
            } while (!$ps->execute($this->_sql->getData()) && ($ps->errorCode() === '23000'));
        } else {
            $this->_sql->insert(static::$_table)->values($updates);
            $ps = $this->_db->prepare($this->_sql->getSql());
            if (!$ps->execute($this->_sql->getData())) {
                return false;
            }
        }
        if ($ps->rowCount() > 0) {
            $this->_updates = [];
            $this->_data[static::$_primary] = $this->_db->getInsertID();
            $this->{static::$_primary} = $this->_data[static::$_primary];
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 不存在则才会创建成功 ---
     * @param array $where 限定条件
     * @param string|null $table 限定表，留空为当前表
     * @return bool
     */
    public function createNotExists(array $where, ?string $table = null) {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->_data[$k];
        }
        if ($table === null) {
            $table = static::$_table;
        }

        if (static::$_key !== '' && !isset($updates[static::$_key])) {
            do {
                $updates[static::$_key] = $this->_keyGenerator();
                $this->_sql->insert(static::$_table)->notExists($table, $updates, $where);
                $ps = $this->_db->prepare($this->_sql->getSql());
            } while (!$ps->execute($this->_sql->getData()) && ($ps->errorCode() === '23000'));
        } else {
            $this->_sql->insert(static::$_table)->notExists($table, $updates, $where);
            $ps = $this->_db->prepare($this->_sql->getSql());
            if (!$ps->execute($this->_sql->getData())) {
                return false;
            }
        }
        if ($ps->rowCount() > 0) {
            $this->_updates = [];
            $this->_data[static::$_primary] = $this->_db->getInsertID();
            $this->{static::$_primary} = $this->_data[static::$_primary];
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 唯一键冲突则替换，不冲突则创建数据 ---
     * @return bool
     */
    public function replace(): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->_data[$k];
        }

        $this->_sql->replace(static::$_table)->values($updates);
        $ps = $this->_db->prepare($this->_sql->getSql());
        if (!$ps->execute($this->_sql->getData())) {
            return false;
        }

        if ($ps->rowCount() > 0) {
            $this->_updates = [];
            $this->_data[static::$_primary] = $this->_db->getInsertID();
            $this->{static::$_primary} = $this->_data[static::$_primary];
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 刷新当前模型获取最新数据 ---
     * @param bool $lock
     * @return bool|null
     */
    public function refresh($lock = false) {
        $this->_sql->select('*', static::$_table)->where([
            static::$_primary => $this->_data[static::$_primary]
        ]);
        if ($lock) {
            $this->_sql->lock();
        }
        $ps = $this->_db->prepare($this->_sql->getSql());
        if (!$ps->execute($this->_sql->getData())) {
            return false;
        }
        if (!($row = $ps->fetch(PDO::FETCH_ASSOC))) {
            return null;
        }
        foreach ($row as $k => $v) {
            $this->_data[$k] = $v;
            $this->$k = $v;
        }
        return true;
    }

    /**
     * --- 更新 set 的数据到数据库 ---
     * @return bool
     */
    public function save(): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->_data[$k];
        }
        if(count($updates) > 0) {
            $this->_sql->update(static::$_table, $updates)->where([
                static::$_primary => $this->_data[static::$_primary]
            ]);
            $ps = $this->_db->prepare($this->_sql->getSql());
            if ($ps->execute($this->_sql->getData())) {
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
     * @param boolean $raw
     * @return bool
     */
    public function remove($raw = false): bool {
        if (static::$_soft && !$raw) {
            $this->_sql->update(static::$_table, [
                'time_remove' => $_SERVER['REQUEST_TIME']
            ])->where([
                static::$_primary => $this->_data[static::$_primary],
                'time_remove' => '0'
            ]);
        } else {
            $this->_sql->delete(static::$_table)->where([
                static::$_primary => $this->_data[static::$_primary]
            ]);
        }
        $ps = $this->_db->prepare($this->_sql->getSql());
        if ($ps->execute($this->_sql->getData()) && ($ps->rowCount() > 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 获取数据库第一个对象 ---
     * @return static|false|null
     */
    public function first() {
        $this->_sql->limit(1);
        $ps = $this->_db->prepare($this->_sql->getSql());
        if ($ps->execute($this->_sql->getData())) {
            if ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $k => $v) {
                    $this->_data[$k] = $v;
                    $this->$k = $v;
                }
                return $this;
            } else {
                return null;
            }
        } else {
            return false;
        }
    }

    /**
     * --- 获取列表 ---
     * @param string|null $key
     * @return false|array
     */
    public function findList(?string $key = null) {
        $ps = $this->_db->prepare($this->_sql->getSql());
        if ($ps->execute($this->_sql->getData())) {
            $list = [];
            while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                $obj = new static([
                    'row' => $row
                ]);
                if ($key) {
                    $list[$row[$key]] = $obj;
                } else {
                    $list[] = $obj;
                }
            }
            return $list;
        } else {
            return false;
        }
    }

    /**
     * --- 动态获取列表，大大减少内存的使用量 ---
     * @return Generator
     */
    public function cursor() {
        $ps = $this->_db->prepare($this->_sql->getSql());
        if ($ps->execute($this->_sql->getData())) {
            while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                yield new static([
                    'row' => $row
                ]);
            }
        }
    }

    /**
     * --- 获取总条数 ---
     * @return int
     */
    public function total(): int {
        $sql = preg_replace('/SELECT .+? FROM/', 'SELECT COUNT(*) AS `count` FROM', $this->_sql->getSql());
        $sql = preg_replace('/ LIMIT [0-9 ,]+/', '', $sql);
        $ps = $this->_db->prepare($sql);
        if ($ps->execute($this->_sql->getData())) {
            if ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                return (int)$row['count'];
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * @param string $f
     * @param array $s
     * @param string $type
     * @return static
     */
    public function join(string $f, array $s = [], $type = 'INNER') {
        $this->_sql->join($f, $s, $type);
        return $this;
    }

    /**
     * --- LIMIT ---
     * @param int $a 起始
     * @param int $b 长度
     * @return static
     */
    public function limit(int $a, int $b = 0) {
        $this->_sql->limit($a, $b);
        return $this;
    }

    /**
     * --- 分页 ---
     * @param int $count 每页条数
     * @param int $page 当前页数
     */
    public function page(int $count, int $page = 1) {
        $this->_sql->limit($count * ($page - 1), $count);
    }

    /**
     * --- 获取值对象 ---
     * @return array
     */
    public function toArray(): array {
        return $this->_data;
    }

    /**
     * --- 当 _key 不为空时，则依据继承此方法的方法自动生成填充 key ---
     * @return string
     */
    protected function _keyGenerator(): string {
        return '';
    }

    // --- 以下为静态方法 ---

    /**
     * --- 获取列表 ---
     * @param array $opt where, by, group, lock, raw
     * @return array
     */

}

