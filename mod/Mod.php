<?php
/**
 * User: JianSuoQiYue
 * Date: 2015
 * Last: 2018-12-15 23:08:01
 */
declare(strict_types = 1);

namespace mod;

use lib\Db;
use lib\Sql;

class Mod {

    // --- 可继承 ---
    protected static $_table = '';
    protected static $_primary = '';

    // --- 设置自己 ---
    /* @var Db $__db */
    protected static $__db = NULL;

    // --- 其他变量 ---
    protected $_updates = [];
    /* @var Db $_db */
    protected $_db = NULL;

    // --- 最后一次 SQL ---
    protected $_lastSqlString = '';
    protected $_lastSqlData = [];

    // --- 静态设置项 ---
    public static function setDb(?Db $db = NULL) {
        self::$__db = $db;
    }

    // --- 获取最后一次 SQL ---
    public function getLastSqlString(): string {
        return $this->_lastSqlString;
    }
    public function getLastSqlData(): array {
        return $this->_lastSqlData;
    }
    public function __setLastSqlString(string $sql): void {
        $this->_lastSqlString = $sql;
    }
    public function __setLastSqlData(array $data): void {
        $this->_lastSqlData = $data;
    }

    // --- 获取创建项 ---
    public static function getCreate() {
        return new static();
    }

    // --- 事物代理操作 ---
    public static function beginTransaction() {
        self::$__db->beginTransaction();
    }
    public static function commit() {
        self::$__db->commit();
    }
    public static function rollBack() {
        self::$__db->rollBack();
    }

    public function __construct() {
        $this->_db = Mod::$__db;
    }

    /**
     * --- 设置模型属性 ---
     * @param string|array $n
     * @param string|int|float $v 可能是数字
     */
    public function set($n, $v = ''): void {
        if(is_array($n)) {
            foreach ($n as $k => $v) {
                // --- 强制更新，因为有的可能就是要强制更新既然设置了 ---
                // if ((isset($this->$k) && ($this->$k != $v)) || !isset($this->$k)) {
                $this->_updates[$k] = true;
                $this->$k = $v;
                // }
            }
        } else {
            // if ((isset($this->$n) && ($this->$n != $v)) || !isset($this->$n)) {
            $this->_updates[$n] = true;
            $this->$n = $v;
            // }
        }
    }

    // --- 更新 ---
    public function update(): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->$k;
        }

        if(count($updates) > 0) {
            $sql = Sql::get();
            try {
                $sql->update(static::$_table, $updates)->where([
                    static::$_primary => $this->{static::$_primary}
                ]);
                $ps = $this->_db->prepare($sql->getSql());

                $this->_lastSqlString = $sql->getSql();
                $this->_lastSqlData = $sql->getData();

                if ($ps->execute($sql->getData())) {
                    $this->_updates = [];
                    return true;
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return true;
        }
    }

    public function remove(): bool {
        $sql = Sql::get();
        try {
            $sql->delete(static::$_table)->where([
                static::$_primary => $this->{static::$_primary}
            ]);
            $ps = $this->_db->prepare($sql->getSql());

            $this->_lastSqlString = $sql->getSql();
            $this->_lastSqlData = $sql->getData();

            if ($ps->execute($sql->getData())) {
                if ($ps->rowCount() > 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    // --- 创建相关常量 ---

    const NORMAL = 0;   // 创建后不获取
    const LOCK = 1;     // 创建后获取并锁定
    const RELOAD = 2;   // 创建后仅获取不锁定

    public function create(int $type = 0): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->$k;
        }

        $sql = Sql::get();
        $sql->insert(static::$_table, $updates);
        $ps = $this->_db->prepare($sql->getSql());

        $this->_lastSqlString = $sql->getSql();
        $this->_lastSqlData = $sql->getData();

        if ($ps->execute($sql->getData())) {
            $this->{static::$_primary} = $this->_db->getInsertID();
            // --- 重新获取 ---
            if ($type === 1 || $type === 2) {
                try {
                    $sql->select('*', static::$_table)->where([
                        static::$_primary => $this->{static::$_primary}
                    ]);
                    if ($type === 1) {
                        $sql->append(' FOR UPDATE');
                    }
                    $ps = $this->_db->prepare($sql->getSql());
                    $ps->execute($sql->getData());
                    $a = $ps->fetch(\PDO::FETCH_ASSOC);
                    foreach ($a as $k => $v) {
                        $this->$k = $v;
                    }
                } catch (\Exception $e) {
                    \sys\log($e->getMessage());
                    return false;
                }
            }
            $this->_updates = [];
            return true;
        } else if ($this->_db->getErrorCode() == 1062) {
            return false;
        } else {
            \sys\log('[Db]' . print_r($this->_db->getErrorInfo(), true) . '(' . $this->_db->getErrorCode() . ')');
            return false;
        }
    }

    // --- 立即执行的自增 ---
    public function increase(string $col, int $num = 1): bool {
        $sql = Sql::get();
        try {
            $sql->update(static::$_table, [
                [$col, '+', $num]
            ])->where([
                static::$_primary => $this->{static::$_primary}
            ]);
            $ps = $this->_db->prepare($sql->getSql());

            $this->_lastSqlString = $sql->getSql();
            $this->_lastSqlData = $sql->getData();

            if ($ps->execute($sql->getData())) {
                if ($ps->rowCount() > 0) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            \sys\log($e->getMessage());
            return false;
        }
    }

    public function toArray(): array {
        $rtn = [];
        foreach ($this as $key => $v) {
            if ($key[0] != '_' && $key != 'table' && $key != 'primary') {
                $rtn[$key] = $v;
            }
        }
        return $rtn;
    }

    /**
     * 需要数据库支持 time_remove 字段
     */
    public function softRemove(): bool {
        $sql = Sql::get();
        try {
            $sql->update(static::$_table, [
                'time_remove' => $_SERVER['REQUEST_TIME']
            ])->where([
                static::$_primary => $this->{static::$_primary},
                'time_remove' => '0'
            ]);
            $ps = $this->_db->prepare($sql->getSql());

            $this->_lastSqlString = $sql->getSql();
            $this->_lastSqlData = $sql->getData();

            if ($ps->execute($sql->getData())) {
                if ($ps->rowCount() > 0) {
                    if (isset($this->time_remove)) {
                        $this->time_remove = $_SERVER['REQUEST_TIME'];
                    }
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            \sys\log($e->getMessage());
            return false;
        }

    }

    // --- 静态方法，获取对象 ---

    /**
     * @param array|string $where
     * @param bool $lock
     * @return Mod|bool
     */
    public static function get($where, bool $lock = false) {
        try {
            $mod = static::class;
            $sql = Sql::get();
            $sql->select('*', static::$_table);
            if (is_array($where)) {
                $sql->where($where);
            } else {
                $sql->append(' WHERE ' . $where);
            }
            if ($lock) {
                $sql->append(' FOR UPDATE');
            }
            $ps = self::$__db->prepare($sql->getSql());
            if ($ps->execute($sql->getData())) {
                if ($obj = $ps->fetchObject($mod)) {
                    return $obj;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    // --- 添加一个序列 ---
    public static function insert(array $cs, array $vs): bool {
        $sql = Sql::get();
        $sql->insert(static::$_table, $cs, $vs);
        $ps = self::$__db->prepare($sql->getSql());
        if ($ps->execute($sql->getData())) {
            if ($ps->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // --- 获取列表, 数组里面是 mod 对象 ---
    public static function getList(array $opt = []): array {
        $opt['where'] = isset($opt['where']) ? $opt['where'] : NULL;
        $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : NULL;
        $opt['by'] = isset($opt['by']) ? $opt['by'] : NULL;
        $opt['group'] = isset($opt['group']) ? $opt['group'] : NULL;
        $opt['array'] = isset($opt['array']) ? $opt['array'] : false;
        $opt['key'] = isset($opt['key']) ? $opt['key'] : false;
        $opt['lock'] = isset($opt['lock']) ? $opt['lock'] : false;
        $opt['select'] = isset($opt['select']) ? $opt['select'] : '*';

        $mod = static::class;
        $sql = Sql::get();
        $sql->select($opt['select'], static::$_table);
        if ($opt['where'] !== NULL) {
            if (is_array($opt['where'])) {
                try {
                    $sql->where($opt['where']);
                } catch (\Exception $e) {
                    \sys\log($e->getMessage());
                    return [];
                }
            } else {
                $sql->append(' WHERE ' . $opt['where']);
            }
        }
        if($opt['group'] !== NULL) {
            $sql->group($opt['group']);
        }
        if($opt['by'] !== NULL) {
            $sql->by($opt['by'][0], $opt['by'][1]);
        }
        $total = NULL;
        if($opt['limit'] !== NULL) {
            if(isset($opt['limit'][2])) {
                // --- 分页 ---
                $sql2 = str_replace(' * ', ' COUNT(0) AS count ', $sql->getSql());
                $ps = self::$__db->prepare($sql2);
                $ps->execute($sql->getData());
                $obj = $ps->fetch(\PDO::FETCH_ASSOC);
                $total = $obj['count'];
                // --- 计算完整 ---
                $sql->limit($opt['limit'][1] * ($opt['limit'][2] - 1), $opt['limit'][1]);
            } else {
                $sql->limit($opt['limit'][0], $opt['limit'][1]);
            }
        }
        if ($opt['lock']) {
            $sql->append(' FOR UPDATE');
        }
        $ps = self::$__db->prepare($sql->getSql());
        $ps->execute($sql->getData());
        $list = [];
        if ($opt['array']) {
            while ($obj = $ps->fetch(\PDO::FETCH_ASSOC)) {
                if ($opt['key']) {
                    $list[$obj[$opt['key']]] = $obj;
                } else {
                    $list[] = $obj;
                }
            }
        } else {
            while ($obj = $ps->fetchObject($mod)) {

                $obj->__setLastSqlString($sql->getSql());
                $obj->__setLastSqlData($sql->getData());

                if ($opt['key']) {
                    $list[$obj->{$opt['key']}] = $obj;
                } else {
                    $list[] = $obj;
                }
            }
        }
        // --- 有分页和无分页返回的不同 ---
        if($total === NULL) {
            return $list;
        } else {
            return [
                'total' => $total,
                'list' => $list
            ];
        }
    }

    // --- 判断某一条记录是否存在/个数 ---

    /**
     * @param $where
     * @param array|bool $opt 设置true代表锁定，否则设置数组
     * @return object|int
     */
    public static function count($where = NULL, $opt = []) {
        $o = [];
        if (is_bool($opt)) {
            $o['lock'] = true;
        } else {
            $o = &$opt;
        }
        $o['select'] = isset($o['select']) ? $o['select'] : 'COUNT(0) AS count';
        $o['lock'] = isset($o['lock']) ? $o['lock'] : false;

        $sql = Sql::get();
        $sql->select($o['select'], static::$_table);
        if ($where != NULL) {
            if (is_array($where)) {
                try {
                    $sql->where($where);
                } catch (\Exception $e) {
                    \sys\log($e->getMessage());
                    return 0;
                }
            } else {
                $sql->append(' WHERE ' . $where);
            }
        }
        // --- 是否锁定 ---
        if ($o['lock']) {
            $sql->append(' FOR UPDATE');
        }
        // --- 开始 ---
        $ps = self::$__db->prepare($sql->getSql());
        $ps->execute($sql->getData());
        $obj = $ps->fetchObject();
        if ($o['select'] == 'COUNT(0) AS count') {
            return $obj->count + 0;
        } else {
            return $obj;
        }
    }

    // --- 静态方法，满足条件则移除 ---
    /**
     * @param array|string $where
     * @return bool
     */
    public static function removeByWhere($where): bool {
        $sql = Sql::get();
        $sql->delete(static::$_table);
        if(is_array($where)) {
            try {
                $sql->where($where);
            } catch (\Exception $e) {
                \sys\log($e->getMessage());
                return false;
            }
        } else {
            $sql->append(' WHERE ' . $where);
        }
        $ps = self::$__db->prepare($sql->getSql());
        if ($ps->execute($sql->getData())) {
            if ($ps->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    // --- 满足条件则更新 ---
    public static function updateByWhere(array $data, $where): bool {
        $sql = Sql::get();
        $sql->update(static::$_table, $data);
        if(is_array($where)) {
            try {
                $sql->where($where);
            } catch (\Exception $e) {
                \sys\log($e->getMessage());
                return false;
            }
        } else {
            $sql->append(' WHERE ' . $where);
        }
        $ps = self::$__db->prepare($sql->getSql());
        if ($ps->execute($sql->getData())) {
            if ($ps->rowCount() > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}

// --- 生成绝对唯一键值 ---
class ModKey extends Mod {

    protected static $_key = '';

    public function create(int $type = 0): bool {
        $updates = [];
        foreach ($this->_updates as $k => $v) {
            $updates[$k] = $this->$k;
        }

        $sql = Sql::get();
        // --- 区别开始 ---
        $column = (static::$_key !== '') ? static::$_key : static::$_primary;
        do {
            $updates[$column] = $this->createKey();
            $sql->insert(static::$_table, $updates);
            $ps = $this->_db->prepare($sql->getSql());
        } while (!($ps->execute($sql->getData())) && ($this->_db->getErrorCode() == 1062));
        if ($ps->rowCount() > 0) {
            $this->{$column} = $updates[$column];
            // --- 重新获取 ---
            if ($type === 1 || $type === 2) {
                try {
                    $sql->select('*', static::$_table)->where([
                        $column => $updates[$column]
                    ]);
                    // --- 区别结束 ---
                    if ($type === 1) {
                        $sql->append(' FOR UPDATE');
                    }
                    $ps = $this->_db->prepare($sql->getSql());

                    $this->_lastSqlString = $sql->getSql();
                    $this->_lastSqlData = $sql->getData();

                    $ps->execute($sql->getData());
                    $a = $ps->fetch(\PDO::FETCH_ASSOC);
                    foreach ($a as $k => $v) {
                        $this->$k = $v;
                    }
                } catch (\Exception $e) {
                    \sys\log($e->getMessage());
                    return false;
                }
            }
            $this->_updates = [];
            return true;
        } else {
            return false;
        }
    }

    public function createKey(): string {
        return '';
    }
}

