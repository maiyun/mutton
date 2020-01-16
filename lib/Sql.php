<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.1","folder":true} - END
 * Date: 2015/6/24 18:55
 * Last: 2019-7-21 00:17:32, 2019-09-17, 2019-12-27 17:11:57
 */
declare(strict_types = 1);

namespace lib;

require ETC_PATH.'sql.php';

class Sql {

    /**
     * --- 获取 Sql 实例 ---
     * @param array|null $etc
     * @return LSql
     */
    public static function get(?array $etc = null): LSql {
        return new LSql($etc);
    }

    /**
     * --- 返回代入后的完整 SQL 字符串 ---
     * @param string $sql SQL 字符串
     * @param array $data DATA 数据
     * @return string
     */
    public static function format(string $sql, array $data): string {
        $i = -1;
        return preg_replace_callback('/\\?/', function () use (&$i, $data) {
            ++$i;
            if (isset($data[$i])) {
                return self::quote($data[$i]);
            } else {
                return '\'\'';
            }
        }, $sql);
    }

    /**
     * --- 转义包裹字符串防注入静态方法 ---
     * @param $str
     * @return string
     */
    public static function quote($str): string {
        if (!is_string($str)) {
            return "'" . $str . "'";
        }
        $rStr = [];
        $len = mb_strlen($str, 'UTF-8');
        for($i = 0; $i < $len; $i++) {
            $chr = mb_substr($str, $i, 1, 'UTF-8');
            switch ($chr) {
                case "\0":
                    $rStr[] = "\\0";
                    break;
                case "'":
                    $rStr[] = "\\'";
                    break;
                case "\"":
                    $rStr[] = "\\\"";
                    break;
                case "\\":
                    $rStr[] = "\\\\";
                    break;
                default:
                    $rStr[] = $chr;
            }
        }
        return "'" . join('', $rStr) . "'";
    }

}

/**
 * --- 需实例化类 ---
 * Class LSql
 * @package lib
 */
class LSql {
    /** @var string 前置 */
    private $_pre = '';

    /** @var array 预拼装 Sql 数组 */
    private $_sql = [];

    /** @var array 所有 data 数据 */
    private $_data = [];

    /**
     * --- 实例化 ---
     * LSql constructor.
     * @param array|null $etc
     */
    public function __construct(?array $etc = null) {
        $this->_pre = $etc && isset($etc['pre']) ? $etc['pre'] : SQL_PRE;
    }

    // --- 配置项 ---
    public function getPre(): string {
        return $this->_pre;
    }
    public function setPre(string $pre): void {
        $this->_pre = $pre;
    }

    // --- 前导 ---

    /**
     * --- 插入 ---
     * @param string $f 表名
     * @param array $cs []
     * @param array $vs [] | [][]
     * @return LSql
     */
    public function insert(string $f, array $cs, array $vs = []): LSql {
        $this->_data = [];
        $sql = 'INSERT' . ' INTO ' . $this->field($f, $this->_pre) . ' (';
        if (count($vs) > 0) {
            // --- 'xx', ['id', 'name'], [['1', 'wow'], ['2', 'oh']] ---
            // --- 'xx', ['id', 'name'], ['1', 'wow'] ---
            foreach ($cs as $i) {
                $sql .= $this->field($i) . ', ';
            }
            $sql = substr($sql, 0, -2) . ') VALUES ';
            // --- 判断插入单条记录还是多条记录 ---
            if (is_array($vs[0])) {
                // --- $vs: [['1', 'wow'], ['2', 'oh']] ---
                // --- 多条记录 ---
                // --- INSERT INTO xx (id, name) VALUES (?, ?), (?, ?) ---
                foreach ($vs as $i => $v) {
                    $sql .= '(';
                    foreach ($v as $i1 => $v1) {
                        $sql .= '?, ';
                        $this->_data[] = $v1;
                    }
                    $sql = substr($sql, 0, -2) . '), ';
                }
                $sql = substr($sql, 0, -2);
            } else {
                // --- $vs: ['1', 'wow'] ---
                // --- 单条记录 ---
                // --- INSERT INTO xx (id, name) VALUES (?, ?) ---
                $sql .= '(';
                foreach ($vs as $i => $v) {
                    $sql .= '?, ';
                }
                $sql = substr($sql, 0, -2) . ')';
                $this->_data = $vs;
            }
        } else {
            // --- 'xx', ['id' => '1', 'name' => 'wow'] ---
            // --- INSERT INTO xx (id, name) VALUES (?, ?) ---
            $values = '';
            foreach ($cs as $k => $v) {
                $sql .= $this->field($k) . ', ';
                $this->_data[] = $v;
                $values .= '?, ';
            }
            $sql = substr($sql, 0, -2) . ') VALUES (' . substr($values, 0, -2) . ')';
        }
        $this->_sql = [$sql];
        return $this;
    }

    /**
     * --- 当不能 insert 时，update（仅能配合 insert 方法用） ---
     * @param array $s 更新数据
     * @return LSql
     */
    public function onDuplicate(array $s): LSql {
        if (count($s) > 0) {
            $sql = ' ON DUPLICATE KEY UPDATE '.$this->_updateSub($s);
            $this->_sql[] = $sql;
        }
        return $this;
    }

    /**
     * --- '*', 'xx' ---
     * @param string|string[] $c 字段
     * @param string|string[] $f 表，允许多张表
     * @return LSql
     */
    public function select($c, $f): LSql {
        $this->_data = [];
        $sql = 'SELECT ';
        if (is_string($c)) {
            $sql .= $this->field($c);
        } else {
            // --- $c: ['id', 'name'] ---
            foreach ($c as $i) {
                $sql .= $this->field($i) . ', ';
            }
            $sql = substr($sql, 0, -2);
        }
        $sql .= ' FROM ';
        if (is_string($f)) {
            $sql .= $this->field($f, $this->_pre);
        } else {
            // --- $f: ['user', 'order'] ---
            foreach ($f as $i) {
                $sql .= $this->field($i, $this->_pre) . ', ';
            }
            $sql = substr($sql, 0, -2);
        }
        $this->_sql = [$sql];
        return $this;
    }

    /**
     * --- UPDATE SQL 方法 ---
     * @param string $f 表名
     * @param array $s 设定 update 的值
     * @return LSql
     */
    public function update(string $f, array $s): LSql {
        $this->_data = [];
        $sql = 'UPDATE ' . $this->field($f, $this->_pre) . ' SET '.$this->_updateSub($s);
        $this->_sql = [$sql];
        return $this;
    }
    private function _updateSub(array $s): string {
        /*
        [
            ['total', '+', '1'],        // 1, '1' 可能也是 1 数字类型
            'type' => '6',              // 2
            'str' => ['(CASE `id` WHEN 1 THEN ? WHEN 2 THEN ? END)', ['val1', 'val2']]      // 3
        ]
        */
        $sql = '';
        foreach ($s as $k => $v) {
            if (is_array($v)) {
                if (isset($v[2])) {
                    // --- 1 ---
                    $if = $this->_isField($v[2]);
                    if ($if[0]) {
                        $sql .= $this->field($v[0]) . ' = ' . $this->field($v[0]) . ' ' . $v[1] . ' ' . $this->field($if[1]) . ', ';
                    } else {
                        $sql .= $this->field($v[0]) . ' = ' . $this->field($v[0]) . ' ' . $v[1] . ' ?, ';
                        $this->_data[] = $if[1];
                    }
                } else {
                    // --- 3 ---
                    $sql .= $this->field($k) . ' = ' . $v[0].', ';
                    if (isset($v[1])) {
                        $this->_data = array_merge($this->_data, $v[1]);
                    }
                }
            } else {
                // --- 2 ---
                $if = $this->_isField($v);
                if ($if[0]) {
                    $sql .= $this->field($k) . ' = ' . $this->field($if[1]) . ', ';
                } else {
                    $sql .= $this->field($k) . ' = ?, ';
                    $this->_data[] = $if[1];
                }
            }
        }
        $sql = substr($sql, 0, -2);
        return $sql;
    }

    /**
     * --- 'xx' ---
     * @param string $f 表名
     * @return LSql
     */
    public function delete(string $f): LSql {
        $this->_data = [];
        $this->_sql = ['DELETE ' . 'FROM ' . $this->field($f, $this->_pre)];
        return $this;
    }

    /**
     * --- join 方法 ---
     * @param string $f 表名
     * @param array $s ON 信息
     * @param string $type 类型
     * @return LSql
     */
    public function join(string $f, array $s = [], $type = 'INNER'): LSql {
        $sql = ' ' . $type . ' JOIN ' . $this->field($f, $this->_pre);
        if (count($s) > 0) {
            $sql .= ' ON ' . $this->_whereSub($s);
        }
        $this->_sql[] = $sql;
        return $this;
    }

    /**
     * --- left join 方法 ---
     * @param string $f 表名
     * @param array $s
     * @return LSql
     */
    public function leftJoin(string $f, array $s = []): LSql {
        return $this->join($f, $s, 'LEFT');
    }

    /**
     * --- right join 方法 ---
     * @param string $f 表名
     * @param array $s
     * @return LSql
     */
    public function rightJoin(string $f, array $s = []): LSql {
        return $this->join($f, $s, 'RIGHT');
    }

    /**
     * --- inner join 方法 ---
     * @param string $f 表名
     * @param array $s
     * @return LSql
     */
    public function innerJoin(string $f, array $s = []): LSql {
        return $this->join($f, $s);
    }

    /**
     * --- full join 方法 ---
     * @param string $f 表名
     * @param array $s
     * @return LSql
     */
    public function fullJoin(string $f, array $s = []): LSql {
        return $this->join($f, $s, 'FULL');
    }

    /**
     * --- cross join 方法 ---
     * @param string $f 表名
     * @param array $s
     * @return LSql
     */
    public function crossJoin(string $f, array $s = []): LSql {
        return $this->join($f, $s, 'CROSS');
    }

    /**
     * --- 筛选器 ---
     * --- 1. ['city' => 'bj', 'type' => '2'] ---
     * --- 2. ['city' => 'bj', ['type', '>', '1']] ---
     * --- 3. ['city' => 'bj', ['type', 'in', ['1', '2']]] ---
     * --- 4. ['city' => 'bj', 'type' => ['1', '2']] ---
     * --- 5. ['$or' => [['city' => 'bj'], ['city' => 'sh']], 'type' => '2'] ---
     * --- 6. ['city_in' => '#city_out'] ---
     * @param array $s 筛选数据
     * @return LSql
     */
    public function where(array $s): LSql {
        if (count($s) > 0) {
            $this->_sql[] = ' WHERE ' . $this->_whereSub($s);
        }
        return $this;
    }
    private function _whereSub(array $s): string {
        $sql = '';
        foreach ($s as $k => $v) {
            if (is_array($v)) {
                // --- 2, 3, 4, 5 ---
                if ($k[0] === '$') {
                    // --- 5 ---
                    $sp = ' ' . strtoupper(substr($k, 1)) . ' ';
                    $sql .= '(';
                    foreach ($v as $k1 => $v1) {
                        if (isset($v1[1]) && is_string($v1[1])) {
                            $sql .= $this->_whereSub([$v1]) . $sp;
                        } else {
                            if (count($v1) > 1) {
                                $sql .= '(' . $this->_whereSub($v1) . ')' . $sp;
                            } else {
                                $sql .= $this->_whereSub($v1) . $sp;
                            }
                        }
                    }
                    $sql = substr($sql, 0, -strlen($sp)) . ') AND ';
                } else if (is_string($k) && is_array($v)) {
                    // --- 4 ---
                    $sql .= $this->field($k) . ' IN (';
                    foreach ($v as $k1 => $v1) {
                        $sql .= '?, ';
                        $this->_data[] = $v1;
                    }
                    $sql = substr($sql, 0, -2) . ') AND ';
                } else if (isset($v[2]) && is_array($v[2])) {
                    // --- 3 ---
                    $sql .= $this->field($v[0]) . ' ' . strtoupper($v[1]) . ' (';
                    foreach ($v[2] as $k1 => $v1) {
                        $sql .= '?, ';
                        $this->_data[] = $v1;
                    }
                    $sql = substr($sql, 0, -2) . ') AND ';
                } else {
                    // --- 2 ---
                    $if = $this->_isField($v[2]);
                    if ($if[0]) {
                        $sql .= $this->field($v[0]) . ' ' . $v[1] . ' ' . $this->field($if[1])  . ' AND ';
                    } else {
                        $sql .= $this->field($v[0]) . ' ' . $v[1] . ' ? AND ';
                        $this->_data[] = $v[2];
                    }
                }
            } else {
                // --- 1 ---
                $if = $this->_isField($v);
                if ($if[0]) {
                    $sql .= $this->field($k) . ' = ' . $this->field($if[1]) . ' AND ';
                } else {
                    $sql .= $this->field($k) . ' = ? AND ';
                    $this->_data[] = $if[1];
                }
            }
        }
        return substr($sql, 0, -5);
    }

    /**
     * --- ORDER BY ---
     * @param string|string[] $c 字段字符串或数组
     * @param string $d 排序规则
     * @return LSql
     */
    public function by($c, string $d = 'DESC'): LSql {
        $sql = ' ORDER BY ';
        if (is_string($c)) {
            $sql .= $this->field($c) . ' ' . $d;
        } else {
            foreach ($c as $k => $v) {
                $sql .= $this->field($v) . ', ';
            }
            $sql = substr($sql, 0, -2) . ' ' . $d;
        }
        $this->_sql[] = $sql;
        return $this;
    }

    /**
     * --- GROUP BY ---
     * @param string|string[] $c 字段字符串或数组
     * @return LSql
     */
    public function group($c): LSql {
        $sql = ' GROUP BY ';
        if (is_string($c)) {
            $sql .= $this->field($c);
        } else {
            foreach ($c as $k => $v) {
                $sql .= $this->field($v) . ', ';
            }
            $sql = substr($sql, 0, -2);
        }
        $this->_sql[] = $sql;
        return $this;
    }

    /**
     * --- LIMIT ---
     * @param int $a 起始
     * @param int $b 长度
     * @return LSql
     */
    public function limit(int $a, int $b = 0): LSql {
        if ($b > 0) {
            $this->_sql[] = ' LIMIT ' . $a . ', ' . $b;
        } else {
            $this->_sql[] = ' LIMIT ' . $a;
        }
        return $this;
    }

    /**
     * --- 追加消极锁，通常不建议使用 ---
     * @return LSql
     */
    public function lock(): LSql {
        $this->_sql[] = ' FOR UPDATE';
        return $this;
    }

    // --- 操作 ---

    /**
     * --- 获取 sql 语句 ---
     * @return string
     */
    public function getSql(): string  {
        return join('', $this->_sql);
    }

    /**
     * --- 获取全部 data ---
     * @return array
     */
    public function getData(): array {
        return $this->_data;
    }

    /**
     * --- 获取带 data 的 sql 语句 ---
     * @param string $sql
     * @param array $data
     * @return string
     */
    public function format(string $sql = '', array $data = []): string {
        if ($sql === '') {
            $sql = $this->getSql();
        }
        if (count($data) === 0) {
            $data = $this->getData();
        }
        return Sql::format($sql, $data);
    }

    // --- 特殊方法 ---

    /**
     * --- 在 sql 最后追加字符串 ---
     * @param string $sql
     * @return LSql
     */
    public function append(string $sql): LSql {
        $this->_sql[] = $sql;
        return $this;
    }

    /**
     * --- 转义包裹字符串防注入静态方法 ---
     * @param $str
     * @return string
     */
    public function quote($str): string {
        return Sql::quote($str);
    }

    /**
     * --- 对字段进行包裹 ---
     * @param string $str
     * @param string $pre 表前缀，仅请在 field 表名时倒入前缀
     * @return string
     */
    public function field(string $str, string $pre = ''): string {
        $str = trim($str);
        $str = str_replace('`', '', $str);  // --- 替换 ` 防止字段内部包含 ` ---
        $str = preg_replace('/  {2,}/', ' ', $str);
        if (preg_match('/^[a-zA-Z0-9_ .-]+?$/', $str)) {
            $loStr = strtolower($str);
            $asPos = strpos($loStr, ' as ');
            $left = '';
            $right = '';
            if ($asPos !== false) {
                $left = substr($str, 0, $asPos);
                $right = ' AS `' . substr($str, $asPos + 4) . '`';
            } else {
                $l = explode(' ', $str);
                $left = $l[0];
                if (isset($l[1])) {
                    $right = ' AS `' . $l[1] . '`';
                }
            }
            $l = explode('.', $left);
            if (!isset($l[1])) {
                return '`' . $pre . $l[0] . '`' . $right;
            }
            return '`' . $l[0] . '`.`' . $pre . $l[1] . '`' . $right;
        } else {
            return $str;
        }
    }

    /**
     * --- 判断用户输入值是否是 field 还是普通字符串 ---
     * @param string|int|float $str
     * @return array
     */
    private function _isField($str): array {
        if (is_string($str) && isset($str[0]) && $str[0] === '#' && isset($str[1])) {
            if ($str[1] === '#') {
                // --- 不是 field ---
                return [false, substr($str, 1)];
            } else {
                // --- 是 field ---
                return [true, substr($str, 1)];
            }
        } else {
            // --- 肯定不是 field ---
            return [false, $str];
        }
    }

}

