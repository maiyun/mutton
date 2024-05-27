<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2015
 * Last: 2018-12-15 23:08:01, 2019-10-2, 2020-2-20 19:34:14, 2020-4-14 13:22:29, 2021-11-30 12:17:21, 2022-3-24 21:57:53, 2022-09-02 23:52:52, 2023-2-3 00:29:16, 2023-6-13 21:47:55, 2023-8-25 15:38:21, 2023-12-21 16:10:11, 2024-2-20 11:50:00, 2024-4-1 19:27:18
 */
declare(strict_types = 1);

namespace mod;

use Generator;
use lib\Db;
use lib\LSql;
use lib\Sql;
use PDO;
use PDOException;

use function sys\log;

class Rows implements \Iterator {

    private $_position = 0;

    private readonly array $_items;

    public function __construct($initialItems) {
        $this->_position = 0;
        $this->_items = $initialItems;
    }

    public function rewind(): void {
        $this->_position = 0;
    }

    public function current(): mixed {
        return $this->_items[$this->_position];
    }

    public function key(): int {
        return $this->_position;
    }

    public function next(): void {
        ++$this->_position;
    }

    public function valid(): bool {
        return isset($this->_items[$this->_position]);
    }
}

/**
 * Class Mod
 * @package mod
 * --- 开启软更需要在表添加字段：ALTER TABLE `table_name` ADD `time_remove` BIGINT NOT NULL DEFAULT '0' AFTER `xxx`; ---
 */
class Mod {

    /** @var string --- 表名 --- */
    protected static $_table = '';
    /** @var string --- 主键字段名 --- */
    protected static $_primary = 'id';
    /** @var string --- 设置后将由 _keyGenerator 函数生成唯一字段 --- */
    protected static $_key = '';
    /** @var bool 可开启软删软更新软新增 */
    protected static $_soft = false;

    /** @var array --- 要 update 的内容 --- */
    protected $_updates = [];
    /** @var array --- 模型获取的属性 --- */
    protected $_data = [];
    /** @var ?string --- 当前选择的分表 _ 后缀 --- */
    protected ?string $_index = null;

    /** @var Db $_db --- 数据库连接对象 --- */
    protected ?Db $_db = null;

    /** @var LSql $_sql --- Sql 对象 --- */
    protected $_sql = null;

    // --- Mutton PHP 框架配置项 [ 开始 ] ---

    /* @var Db $__db 设置映射静态数据库对象 */
    protected static $__db = null;

    /** @var ?string --- 设置 Sql 库的 pre 配置 --- */
    protected static ?string $__pre = null;

    // --- Mutton PHP 框架配置项 [ 结束 ] ---

    /**
     * 构造函数
     * @param array $opt index, alias, row, select, where, raw
     */
    public function __construct(array $opt = []) {
        // --- 导入数据库连接 ---
        $this->_db = Mod::$__db;
        // --- 新建 sql 对象 ---
        $this->_sql = Sql::get(Mod::$__pre);
        if (isset($opt['index'])) {
            $this->_index = $opt['index'];
        }
        // --- 第三个参数用于内部数据导入，将 data 数据合并到本实例化类 ---
        if (isset($opt['row'])) {
            foreach ($opt['row'] as $k => $v) {
                $this->_data[$k] = $v;
                $this->$k = $v;
            }
        }
        /** --- 是否有 select --- */
        $select = isset($opt['select']) ? $opt['select'] : (isset($opt['where']) ? '*' : '');
        if ($select) {
            $this->_sql->select(
                $select,
                static::$_table .
                ($this->_index !== null ? ('_' . $this->_index) : '') .
                (isset($opt['alias']) ? ' ' . $opt['alias'] : '')
            ); 
        }
        if (isset($opt['where'])) {
            if (static::$_soft && (!isset($opt['raw']) || !$opt['raw'])) {
                if (is_string($opt['where'])) {
                    $opt['where'] = $opt['where'] ? ('(' . $opt['where'] . ') AND ') : '`time_remove` = 0';
                }
                else {
                    $opt['where']['time_remove'] = '0';
                }
            }
            $this->_sql->where($opt['where']);
        }
    }

    // --- Mutton: true, Kebab: false [ 开始 ] ---

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

    // --- Mutton: true, Kebe: false [ 结束 ] ---

    // --- 静态方法 ---

    /**
     * --- 添加一个序列 ---
     * @param array $cs 字段列表
     * @param array $vs 数据列表
     * @param string $index 分表后缀
     * @return bool|null
     */
    public static function insert(array $cs, array $vs = [], $index = null) {
        $sql = Sql::get(Mod::$__pre);
        $sql->insert(static::$_table . ($index !== null ? ('_' . $index) : ''))->values($cs, $vs);
        $ps = self::$__db->prepare($sql->getSql());
        try {
            $ps->execute($sql->getData());
        }
        catch (PDOException $e) {
            log('[insert, mod]' . $e->getMessage(), '-error');
            return false;
        }
        if ($ps->rowCount() > 0) {
            return true;
        }
        else {
            return null;
        }
    }

    /**
     * --- 获取添加一个序列的模拟 SQL ---
     * @param array $cs 字段列表
     * @param array $vs 数据列表
     * @param string $index 分表后缀
     * @return string
     */
    public static function insertSql(array $cs, array $vs = [], $index = null): string {
        $sql = Sql::get(Mod::$__pre);
        $sql->insert(static::$_table . ($index !== null ? ('_' . $index) : ''))->values($cs, $vs);
        return $sql->format();
    }

    /**
     * --- 插入数据如果唯一键冲突则更新 ---
     * @param array $data 要插入的数据
     * @param array $update 要更新的数据
     * @param int $index 分表后缀
     * @return bool|null
     */
    public static function insertDuplicate(array $data, array $update, $index = null) {
        $sql = Sql::get(Mod::$__pre);
        $sql->insert(static::$_table . ($index !== null ? ('_' . $index) : ''))->values($data)->duplicate($update);
        $ps = self::$__db->prepare($sql->getSql());
        try {
            $ps->execute($sql->getData());
        }
        catch (PDOException $e) {
            log('[insertDuplicate, mod]' . $e->getMessage(), '-error');
            return false;
        }
        if ($ps->rowCount() > 0) {
            return true;
        }
        else {
            return null;
        }
    }

    /**
     * --- 根据条件移除条目 ---
     * @param string|array $where 筛选条件
     * @param array $opt index, raw, by, limit
     * @return bool|null
     */
    public static function removeByWhere($where, array $opt = []) {
        $sql = Sql::get(Mod::$__pre);
        if (static::$_soft && (!isset($opt['raw']) || !$opt['raw'])) {
            // --- 软删除 ---
            $sql->update(static::$_table . (isset($opt['index']) ? ('_' . $opt['index']) : ''), [
                'time_remove' => time()
            ]);
            if (is_string($where)) {
                $where = '(' . $where . ') AND `time_remove` = 0';
            }
            else {
                $where['time_remove'] = '0';
            }
        }
        else {
            // --- 真删除 ---
            $sql->delete(static::$_table . (isset($opt['index']) ? ('_' . $opt['index']) : ''));
        }
        $sql->where($where);
        if ($opt['by']) {
            $sql->by($opt['by'][0], isset($opt['by'][1]) ? $opt['by'][1] : 'DESC');
        }
        if ($opt['limit']) {
            $sql->limit($opt['limit'][0], isset($opt['limit'][1]) ? $opt['limit'][1] : 0);
        }
        $ps = self::$__db->prepare($sql->getSql());
        try {
            $ps->execute($sql->getData());
        }
        catch (PDOException $e) {
            log('[removeByWhere, mod]' . $e->getMessage(), '-error');
            return false;
        }
        $rc = $ps->rowCount();
        if ($rc > 0) {
            return $rc;
        }
        return null;
    }

    /**
     * --- 根据条件移除条目（仅获取 SQL 对象） ---
     * @param string|array $where 筛选条件
     * @param array $opt index, raw, by, limit
     */
    public static function removeByWhereSql($where, array $opt = []): LSql {
        $sql = Sql::get(Mod::$__pre);
        if (static::$_soft && (!isset($opt['raw']) || !$opt['raw'])) {
            // --- 软删除 ---
            $sql->update(static::$_table . (isset($opt['index']) ? ('_' . $opt['index']) : ''), [
                'time_remove' => time()
            ]);
            if (is_string($where)) {
                $where = '(' . $where . ') AND `time_remove` = 0';
            }
            else {
                $where['time_remove'] = '0';
            }
        }
        else {
            // --- 真删除 ---
            $sql->delete(static::$_table . (isset($opt['index']) ? ('_' . $opt['index']) : ''));
        }
        $sql->where($where);
        if ($opt['by']) {
            $sql->by($opt['by'][0], isset($opt['by'][1]) ? $opt['by'][1] : 'DESC');
        }
        if ($opt['limit']) {
            $sql->limit($opt['limit'][0], isset($opt['limit'][1]) ? $opt['limit'][1] : 0);
        }
        return $sql;
    }

    /**
     * --- 根据条件更新数据 ---
     * @param array $data 要更新的数据
     * @param array|string $where 筛选条件，或 index 分表后缀
     * @param array $opt index, raw, by, limit
     * @return bool|null
     */
    public static function updateByWhere(array $data, $where, array $opt = []) {
        $sql = Sql::get(Mod::$__pre);
        $sql->update(static::$_table . (isset($opt['index']) ? ('_' . $opt['index']) : ''), $data);
        if (static::$_soft && (!isset($opt['raw']) || !$opt['raw'])) {
            if (is_string($where)) {
                $where = '(' . $where . ') AND `time_remove` = 0';
            }
            else {
                $where['time_remove'] = '0';
            }
        }
        $sql->where($where);
        if (isset($opt['by'])) {
            $sql->by($opt['by'][0], isset($opt['by'][1]) ? $opt['by'][1] : 'DESC');
        }
        if (isset($opt['limit'])) {
            $sql->limit($opt['limit'][0], isset($opt['limit'][1]) ? $opt['limit'][1] : 0);
        }
        $ps = self::$__db->prepare($sql->getSql());
        try {
            $ps->execute($sql->getData());
        }
        catch (PDOException $e) {
            log('[updateByWhere, mod]' . $e->getMessage(), '-error');
            return false;
        }
        $rc = $ps->rowCount();
        if ($rc > 0) {
            return $rc;
        }
        return null;
    }

    /**
     * --- 根据条件更新数据（仅获取 SQL 对象） ---
     * @param array $data 要更新的数据
     * @param array|string $where 筛选条件
     * @param array $opt index, raw, by, limit
     */
    public static function updateByWhereSql(array $data, $where, array $opt = []): LSql {
        $sql = Sql::get(Mod::$__pre);
        $sql->update(static::$_table . (isset($opt['index']) ? ('_' . $opt['index']) : ''), $data);
        if (static::$_soft && (!isset($opt['raw']) || !$opt['raw'])) {
            if (is_string($where)) {
                $where = '(' . $where . ') AND `time_remove` = 0';
            }
            else {
                $where['time_remove'] = '0';
            }
        }
        $sql->where($where);
        if (isset($opt['by'])) {
            $sql->by($opt['by'][0], isset($opt['by'][1]) ? $opt['by'][1] : 'DESC');
        }
        if (isset($opt['limit'])) {
            $sql->limit($opt['limit'][0], isset($opt['limit'][1]) ? $opt['limit'][1] : 0);
        }
        return $sql;
    }

    /**
     * --- select 自定字段 ---
     * @param string|string[]|string[][] $c 字段字符串或字段数组
     * @param array $opt index alias
     * @return static
     */
    public static function select($c, $opt = []) {
        $opt['select'] = $c;
        return new static($opt);
    }

    /**
     * --- 通过 where 条件获取模型 ---
     * @param array|string $s 筛选条件数组或字符串
     * @param bool|?string $raw 是否包含已被软删除的数据
     * @param string $index 分表后缀
     * @return static
     */
    public static function where($s = '', $raw = false, $index = null) {
        if (!is_bool($raw)) {
            $index = $raw;
            $raw = false;
        }
        return new static([
            'where' => $s,
            'raw' => $raw,
            'index' => $index
        ]);
    }

    /**
     * --- 获取创建对象，通常用于新建数据库条目 ---
     * @param string $index 分表后缀
     * @return static
     */
    public static function getCreate($index = null) {
        return new static([
            'index' => $index
        ]);
    }

    /**
     * --- 根据主键获取对象 ---
     * @param string|int|float $val 主键值
     * @param bool $lock 是否加锁
     * @param bool|?string $raw 是否获取真实数据
     * @param string $index 分表后缀
     * @return bool|null|static
     */
    public static function find($val, $lock = false, $raw = false, $index = null) {
        if (!is_bool($raw)) {
            $index = $raw;
            $raw = false;
        }
        if (!is_string($val) && !is_numeric($val)) {
            return null;
        }
        return (new static([
            'where' => [
                static::$_primary => $val
            ],
            'raw' => $raw,
            'index' => $index
        ]))->first($lock);
    }

    /**
     * --- 通过 where 条件筛选单条数据 ---
     * @param array|string $s 筛选条件数组或字符串
     * @param array $opt raw, index, select, array
     * @return false|null|static|array
     */
    public static function one($s, $opt = []) {
        $opt['where'] = $s;
        if (!isset($opt['index'])) {
            $o = new static($opt);
            return (isset($opt['array']) && $opt['array']) ? $o->firstArray() : $o->first();
        }
        if (is_string($opt['index'])) {
            $opt['index'] = [$opt['index']];
        }
        foreach ($opt['index'] as $item) {
            $opt['index'] = $item;
            $row = (new static($opt));
            $rowr = (isset($opt['array']) && $opt['array']) ? $row->firstArray() : $row->first();
            if ($rowr) {
                return $rowr;
            }
            if ($rowr === false) {
                return false;
            }
            // --- 如果是 null 再去下个 index 找一下 ---
        }
        return null;
    }

    /**
     * --- 通过 where 条件筛选单条数据返回原生对象 ---
     * @param array|string $s 筛选条件数组或字符串
     * @param array $opt raw, index, select
     * @return false|null|array
     */
    public static function oneArray($s, $opt = []) {
        $opt['array'] = true;
        return self::one($s, $opt);
    }

    /**
     * --- 根据 where 条件获取主键值列表 ---
     * @param array|string $where where 条件
     * @param boolean|?string $raw 是否包含已被软删除的主键值
     * @param string $index 分表后缀
     * @return array|false
     */
    public static function primarys($where = '', $raw = false, $index = null) {
        if (!is_bool($raw)) {
            $index = $raw;
            $raw = false;
        }
        $sql = Sql::get(Mod::$__pre);
        if (static::$_soft && !$raw) {
            // --- 不包含已删除 ---
            if (is_string($where)) {
                if ($where !== '') {
                    $where = '(' . $where . ') AND `time_remove` = 0';
                }
            }
            else {
                $where['time_remove'] = '0';
            }
        }
        $sql->select(self::$_primary, static::$_table . ($index !== null ? ('_' . $index) : ''))->where($where);
        $ps = self::$__db->prepare($sql->getSql());
        try {
            $ps->execute($sql->getData());
        }
        catch (PDOException $e) {
            log('[primarys, mod]' . $e->getMessage(), '-error');
            return false;
        }
        $primarys = [];
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            $primarys[] = $row[self::$_primary];
        }
        return $primarys;
    }

    /**
     * --- 将 key val 组成的数据列表转换为原生对象模式 ---
     * @param this[] $obj 要转换的 kv 数据列表
     */
    public static function toArrayByRecord(array $obj): array {
        $rtn = [];
        foreach ($obj as $key => $val) {
            $rtn[$key] = $val->toArray();
        }
        return $rtn;
    }

    // --- 动态方法 ---

    /**
     * --- 设置一个/多个属性 ---
     * @param string|array $n 字符串或键/值
     * @param string|int|float|null|array $v 可能是数字
     */
    public function set($n, $v = ''): void {
        if(!is_string($n)) {
            // --- [ x => y ] ---
            foreach ($n as $k => $v) {
                // --- 强制更新，因为有的可能就是要强制更新既然设置了 ---
                $this->_updates[$k] = true;
                $this->_data[$k] = $v;
                $this->$k = $v;
            }
        }
        else {
            // --- x, y ---
            if (!is_string($n)) {
                return;
            }
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
     * @param array|null $notWhere 若要不存在才成功，则要传入限定条件
     * @param string|null $table 可对限定条件传入适当的表
     * @return bool
     */
    public function create(?array $notWhere = null, ?string $table = null): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->_data[$k];
        }
        // --- 这个 table 主要给 notWhere 有值时才使用 ---
        if (!$table) {
            $table = static::$_table . ($this->_index !== null ? ('_' . $this->_index) : '');
        }

        $ps = null;
        if ((static::$_key !== '') && !isset($updates[static::$_key])) {
            $count = 0;
            while (true) {
                if ($count === 3) {
                    return false;
                }
                $updates[static::$_key] = $this->_keyGenerator();
                $this->_data[static::$_key] = $updates[static::$_key];
                $this->{static::$_key} = $updates[static::$_key];
                $this->_sql->insert(static::$_table . ($this->_index !== null ? ('_' . $this->_index) : ''));
                if ($notWhere) {
                    $this->_sql->notExists($table, $updates, $notWhere);
                }
                else {
                    $this->_sql->values($updates);
                }
                $ps = $this->_db->prepare($this->_sql->getSql());
                ++$count;
                try {
                    $ps->execute($this->_sql->getData());
                    break;
                }
                catch (PDOException $e) {
                    if ($e->errorInfo[0] !== '23000') {
                        log('[create0, mod]' . $e->getMessage(), '-error');
                        return false;
                    }
                }
            }
        }
        else {
            $this->_sql->insert(static::$_table . ($this->_index !== null ? ('_' . $this->_index) : ''));
            if ($notWhere) {
                $this->_sql->notExists($table, $updates, $notWhere);
            }
            else {
                $this->_sql->values($updates);
            }
            $ps = $this->_db->prepare($this->_sql->getSql());
            try {
                $ps->execute($this->_sql->getData());
            }
            catch (PDOException $e) {
                log('[create1, mod]' . $e->getMessage(), '-error');
                return false;
            }
        }
        if ($ps->rowCount() > 0) {
            $this->_updates = [];
            $this->_data[static::$_primary] = $this->_db->getInsertID();
            $this->{static::$_primary} = $this->_data[static::$_primary];
            return true;
        }
        else {
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

        $this->_sql->replace(static::$_table . ($this->_index !== null ? ('_' . $this->_index) : ''))->values($updates);
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[replace, mod]' . $e->getMessage(), '-error');
            return false;
        }

        if ($ps->rowCount() > 0) {
            $this->_updates = [];
            $this->_data[static::$_primary] = $this->_db->getInsertID();
            $this->{static::$_primary} = $this->_data[static::$_primary];
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * --- 刷新当前模型获取最新数据 ---
     * @param bool $lock 是否加锁
     * @return bool|null
     */
    public function refresh($lock = false) {
        $this->_sql->select('*', static::$_table . ($this->_index !== null ? ('_' . $this->_index) : ''))->where([
            static::$_primary => $this->_data[static::$_primary]
        ]);
        if ($lock) {
            $this->_sql->lock();
        }
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[refresh, mod]' . $e->getMessage(), '-error');
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
        if (count($updates) === 0) {
            return true;
        }
        $this->_sql->update(static::$_table . ($this->_index !== null ? ('_' . $this->_index) : ''), $updates)->where([
            static::$_primary => $this->_data[static::$_primary]
        ]);
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            $this->_updates = [];
            return true;
        }
        catch (PDOException $e) {
            log('[save, mod]' . $e->getMessage(), '-error');
            return false;
        }
    }

    /**
     * --- 移除本条目 ---
     * @param boolean $raw 是否真实移除
     * @return bool
     */
    public function remove($raw = false): bool {
        if (static::$_soft && !$raw) {
            $this->_sql->update(static::$_table . ($this->_index !== null ? ('_' . $this->_index) : ''), [
                'time_remove' => $_SERVER['REQUEST_TIME']
            ])->where([
                static::$_primary => $this->_data[static::$_primary],
                'time_remove' => '0'
            ]);
        }
        else {
            $this->_sql->delete(static::$_table . ($this->_index !== null ? ('_' . $this->_index) : ''))->where([
                static::$_primary => $this->_data[static::$_primary]
            ]);
        }
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[remove, mod]' . $e->getMessage(), '-error');
            return false;
        }
        if ($ps->rowCount() > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * --- 获取数据库第一个对象 ---
     * @param bool $lock 是否加锁
     * @param bool $array 是否返回原生对象
     * @return static|false|null
     */
    public function first(bool $lock = false, bool $array = false) {
        $this->_sql->limit(1);
        if ($lock) {
            $this->_sql->lock();
        }
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[first, mod]' . $e->getMessage(), '-error');
            return false;
        }
        if (!($row = $ps->fetch(PDO::FETCH_ASSOC))) {
            return null;
        }
        if ($array) {
            return $row;
        }
        foreach ($row as $k => $v) {
            $this->_data[$k] = $v;
            $this->$k = $v;
        }
        return $this;
    }

    /**
     * --- 获取数据库第一个原生对象 ---
     * @param bool $lock 是否加锁
     * @return array|false|null
     */
    public function firstArray(bool $lock = false) {
        return $this->first($lock, true);
    }
    
    /**
     * --- 联合查询表数据 ---
     * @param $f 要联合查询的表列表，或单个表
     * @param $type 类型
     */
    public function union($f, string $type = '') {
        if (is_string($f)) {
            $f = [
                'field' => $f
            ];
        }
        if (!is_string($f[0])) {
            $f = [$f];
        }
        foreach ($f as $item) {
            if (is_string($item)) {
                $item = [
                    'field' => $item
                ];
            }
            $this->_sql->union($this->_sql->copy($item['field'], [
                'where' => $item['where']
            ]), $type);
        }
        return $this;
    }

    /**
     * --- 联合查询表数据 ---
     * @param $f 要联合查询的表列表，或单个表
     */
    public function unionAll(array|string $f) {
        if (is_string($f)) {
            $f = [
                'field' => $f
            ];
        }
        if (!is_string($f[0])) {
            $f = [$f];
        }
        foreach ($f as $item) {
            if (is_string($item)) {
                $item = [
                    'field' => $item
                ];
            }
            $this->_sql->unionAll($this->_sql->copy($item['field'], [
                'where' => $item['where']
            ]));
        }
        return $this;
    }

    /**
     * --- 获取列表 ---
     * @param string|null $key 是否以某个字段为主键
     * @return false|Rows
     */
    public function all(?string $key = null) {
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[all, mod]' . $e->getMessage(), '-error');
            return false;
        }
        $list = [];
        if ($key) {
            while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                $obj = new static([
                    'row' => $row,
                    'index' => $this->_index
                ]);
                $list[$row[$key]] = $obj;
            }
            return $list;
        }
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            $obj = new static([
                'row' => $row,
                'index' => $this->_index
            ]);
            $list[] = $obj;
        }
        return $list;
    }

    /**
     * --- 获取列表（得到的为原生对象或数组，不是模型） ---
     * @param string|null $key 是否以某个字段为主键
     * @return false|array
     */
    public function allArray(?string $key = null) {
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[allArray, mod]' . $e->getMessage(), '-error');
            return false;
        }
        $list = [];
        if ($key) {
            while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                $list[$row[$key]] = $row;
            }
            return $list;
        }
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * --- 获取数查询（SELECT）扫描情况，获取字符串或kv数组 ---
     * @param bool $all 是否获取完全的情况，默认不获取，只返回扫描情况
     * @return false|array|string
     */
    public function explain($all = false) {
        $ps = $this->_db->prepare('EXPLAIN ' . $this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[explain, mod]' . $e->getMessage(), '-error');
            return false;
        }
        if (!($row = $ps->fetch(PDO::FETCH_ASSOC))) {
            return false;
        }
        if (!$all) {
            return $row['type'];
        }
        return $row;
    }

    /**
     * --- 动态获取列表，大大减少内存的使用量（Mutton: true, Kebab: false） ---
     * @return Generator|boolean
     */
    public function cursor() {
        $ps = $this->_db->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[cursor, mod]' . $e->getMessage(), '-error');
            return false;
        }
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            yield new static([
                'row' => $row,
                'index' => $this->_index
            ]);
        }
    }

    /**
     * --- 获取总条数，自动抛弃 LIMIT，仅用于获取数据的情况（select） ---
     * @return int
     */
    public function total(string $f = '*'): int {
        $sql = preg_replace('/SELECT .+? FROM/', 'SELECT COUNT(' . $this->_sql->field($f) . ') AS `count` FROM', $this->_sql->getSql());
        $sql = preg_replace('/ LIMIT [0-9 ,]+/', '', $sql);
        $ps = $this->_db->prepare($sql);
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[total, mod]' . $e->getMessage(), '-error');
            return 0;
        }
        $count = 0;
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            $count += (int)$row['count'];
        }
        return $count;
    }

    /**
     * --- 根据当前条件，筛选出当前条目该有的数据条数 ---
     * @return int
     */
    public function count(): int{
        $sql = preg_replace('/SELECT .+? FROM/', 'SELECT COUNT(*) AS `count` FROM', $this->_sql->getSql());
        $ps = $this->_db->prepare($sql);
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[count, mod]' . $e->getMessage(), '-error');
            return 0;
        }
        $count = 0;
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            $count += (int)$row['count'];
        }
        return $count;
    }

    /**
     * @param string $f 表名
     * @param array $s ON 信息
     * @param string $type 类型
     * @param string $index 给本表增加 index 分表项
     * @return static
     */
    public function join(string $f, array $s = [], $type = 'INNER', string $index = '') {
        $this->_sql->join($f, $s, $type, $index ? '_' + $index : '');
        return $this;
    }

    /**
     * --- left join 方法 ---
     * @param string $f 表名
     * @param array $s ON 信息
     * @param string $index 给本表增加 index 分表项
     * @return static
     */
    public function leftJoin(string $f, array $s = [], string $index = '') {
        $this->_sql->leftJoin($f, $s, $index ? '_' + $index : '');
        return $this;
    }

    /**
     * --- right join 方法 ---
     * @param string $f 表名
     * @param array $s ON 信息
     * @param string $index 给本表增加 index 分表项
     * @return static
     */
    public function rightJoin(string $f, array $s = [], string $index = '') {
        $this->_sql->rightJoin($f, $s, $index ? '_' + $index : '');
        return $this;
    }

    /**
     * --- inner join 方法 ---
     * @param string $f 表名
     * @param array $s ON 信息
     * @param string $index 给本表增加 index 分表项
     * @return static
     */
    public function innerJoin(string $f, array $s = [], string $index = '') {
        $this->_sql->innerJoin($f, $s, $index ? '_' + $index : '');
        return $this;
    }

    /**
     * --- full join 方法 ---
     * @param string $f 表名
     * @param array $s ON 信息
     * @param string $index 给本表增加 index 分表项
     * @return static
     */
    public function fullJoin(string $f, array $s = [], string $index = '') {
        $this->_sql->fullJoin($f, $s, $index ? '_' + $index : '');
        return $this;
    }

    /**
     * --- cross join 方法 ---
     * @param string $f 表名
     * @param array $s ON 信息
     * @param string $index 给本表增加 index 分表项
     * @return static
     */
    public function crossJoin(string $f, array $s = [], string $index = '') {
        $this->_sql->crossJoin($f, $s, $index ? '_' + $index : '');
        return $this;
    }

    /**
     * --- 筛选器 ---
     * @param array|string $s 筛选条件数组或字符串
     * @return static
     */
    public function having($s) {
        $this->_sql->having($s);
        return $this;
    }

    /**
     * --- 筛选器 ---
     * @param array|string $s 筛选条件数组或字符串
     * @param bool $raw 是否包含已被软删除的数据
     * @return static
     */
    public function filter($s, $raw = false) {
        if (static::$_soft && !$raw) {
            if (is_string($s)) {
                $s = '(' . $s . ') AND `time_remove` = 0';
            }
            else {
                $s['time_remove'] = '0';
            }
        }
        $this->_sql->where($s);
        return $this;
    }

    /**
     * --- ORDER BY ---
     * @param string|string[] $c 字段字符串或数组
     * @param string $d 排序规则
     * @return static
     */
    public function by($c, string $d = 'DESC') {
        $this->_sql->by($c, $d);
        return $this;
    }

    /**
     * --- GROUP BY ---
     * @param string|string[] $c 字段字符串或数组
     * @return static
     */
    public function group($c) {
        $this->_sql->group($c);
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
     * @return static
     */
    public function page(int $count, int $page = 1) {
        $this->_sql->limit($count * ($page - 1), $count);
        return $this;
    }

    /**
     * --- 在 sql 最后追加字符串 ---
     * @param string $sql
     * @return static
     */
    public function append(string $sql) {
        $this->_sql->append($sql);
        return $this;
    }

    /**
     * --- 获取 sql 语句 ---
     * @return string
     */
    public function getSql(): string {
        return $this->_sql->getSql();
    }

    /**
     * --- 获取全部 data ---
     * @return array
     */
    public function getData(): array {
        return $this->_sql->getData();
    }

    /**
     * --- 获取带 data 的 sql 语句 ---
     * @param string|null $sql sql 语句
     * @param array|null $data 数据
     * @return string
     */
    public function format(string $sql = null, array $data = null): string {
        return $this->_sql->format($sql, $data);
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
}

