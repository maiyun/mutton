<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/6/24 18:55
 * Last: 2019-7-21 00:17:32
 */
declare(strict_types = 1);

namespace lib;

require ETC_PATH.'sql.php';

class Sql {
    private $_pre = '';
    private $_sql = [];
    private $_data = [];

    /* @var $_db Db */
    private $_db = NULL;

    // --- 获取 Sql 实例 ---
    public static function get(?string $pre = NULL): Sql {
        return new Sql($pre);
    }

    // --- 实例化 ---
    public function __construct(?string $pre = NULL) {
        $this->_pre = $pre ? $pre : SQL_PRE;
    }

    // --- 配置项 ---
    public function setDb(Db $db): Sql {
        $this->_db = $db;
        return $this;
    }
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
     * @return Sql
     */
    public function insert(string $f, array $cs = [], array $vs = []): Sql {
        $this->_data = [];
        $sql = 'INSERT' . ' INTO ' . $this->_pre . $f . ' (';
        if (count($vs) > 0) {
            // --- 'xx', ['id', 'name'], [['1', 'wow'], ['2', 'oh']] ---
            // --- 'xx', ['id', 'name'], ['1', 'wow'] ---
            foreach ($cs as $i) {
                $sql .= $this->field($i) . ',';
            }
            $sql = substr($sql, 0, -1) . ') VALUES ';
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
     * @return Sql
     */
    public function onDuplicate(array $s): Sql {
        if (count($s) > 0) {
            $sql = ' ON DUPLICATE KEY UPDATE '.$this->_updateSub($s);
            $this->_sql[] = $sql;
        }
        return $this;
    }

    /**
     * --- '*', 'xx' ---
     * @param string|array $c 字段
     * @param string $f 表
     * @return Sql
     */
    public function select($c, string $f): Sql {
        $this->_data = [];
        $sql = 'SELECT ';
        if (is_string($c)) {
            $sql .= $c;
        } else {
            // --- $c: ['id', 'name'] ---
            foreach ($c as $i) {
                $sql .= $this->field($i) . ',';
            }
            $sql = substr($sql, 0, -1);
        }
        $sql .= ' FROM ' . $this->_pre . $f;
        $this->_sql = [$sql];
        return $this;
    }

    /**
     * --- UPDATE SQL 方法 ---
     * @param string $f 表名
     * @param array $s 设定 update 的值
     * @return Sql
     */
    public function update(string $f, array $s): Sql {
        $this->_data = [];
        $sql = 'UPDATE ' . $this->_pre . $f . ' SET '.$this->_updateSub($s);
        $this->_sql = [$sql];
        return $this;
    }
    private function _updateSub(array $s): string {
        /*
        [
            ['total', '+', '1'],        // 1
            'type' => '6',              // 2
            'str' => ['(CASE `id` WHEN 1 THEN ? WHEN 2 THEN ? END)', ['val1', 'val2']]      // 3
        ]
        */
        $sql = '';
        foreach ($s as $k => $v) {
            if (is_array($v)) {
                if (isset($v[2]) && is_string($v[2])) {
                    // --- 1 ---
                    $sql .= $this->field($v[0]) . ' = ' . $this->field($v[0]) . ' ' . $v[1] . ' ?,';
                    $this->_data[] = $v[2];
                } else {
                    // --- 3 ---
                    $sql .= $this->field($k) . ' = ' . $v[0].',';
                    if (isset($v[1])) {
                        $this->_data = array_merge($this->_data, $v[1]);
                    }
                }
            } else {
                // --- 2 ---
                $sql .= $this->field($k) . ' = ?,';
                $this->_data[] = $v;
            }
        }
        $sql = substr($sql, 0, -1);
        return $sql;
    }

    /**
     * --- 'xx' ---
     * @param string $f 表名
     * @return Sql
     */
    public function delete(string $f): Sql {
        $this->_data = [];
        $this->_sql = ['DELETE ' . 'FROM ' . $this->_pre . $f];
        return $this;
    }

    /**
     * --- 筛选器 ---
     * --- 1. ['city' => 'bj', 'type' => '2'] ---
     * --- 2. ['city' => 'bj', ['type', '>', '1']] ---
     * --- 3. ['city' => 'bj', ['type', 'in', ['1', '2']]] ---
     * --- 4. ['city' => 'bj', 'type' => ['1', '2']] ---
     * --- 5. ['$or' => [['city' => 'bj'], ['city' => 'sh']], 'type' => '2'] ---
     * @param array $s 筛选数据
     * @return Sql
     */
    public function where(array $s): Sql {
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
                    $sql .= $this->field($v[0]) . ' ' . $v[1] . ' ? AND ';
                    $this->_data[] = $v[2];
                }
            } else {
                // --- 1 ---
                $sql .= $this->field($k) . ' = ? AND ';
                $this->_data[] = $v;
            }
        }
        return substr($sql, 0, -5);
    }

    /**
     * --- ORDER BY ---
     * @param string|array $c 字段字符串或数组
     * @param string $d 排序规则
     * @return Sql
     */
    public function by($c, string $d = 'DESC'): Sql {
        $sql = ' ORDER BY ';
        if (is_string($c)) {
            $sql .= $c . ' ' . $d;
        } else {
            foreach ($c as $k => $v) {
                $sql .= $this->field( $v) . ',';
            }
            $sql = substr($sql, 0, -1) . ' ' . $d;
        }
        $this->_sql[] = $sql;
        return $this;
    }

    /**
     * --- GROUP BY ---
     * @param string|array $c 字段字符串或数组
     * @return Sql
     */
    public function groupBy($c): Sql {
        $sql = ' GROUP BY ';
        if (is_string($c)) {
            $sql .= $c;
        } else {
            foreach ($c as $k => $v) {
                $sql .= $this->field($v) . ',';
            }
            $sql = substr($sql, 0, -1);
        }
        $this->_sql[] = $sql;
        return $this;
    }

    /**
     * --- LIMIT ---
     * @param int $a 起始
     * @param int $b 长度
     * @return Sql
     */
    public function limit(int $a, int $b): Sql {
        $this->_sql[] = ' LIMIT ' . $a . ', ' . $b;
        return $this;
    }

    // --- 操作 ---

    public function getSql(): string  {
        return join('', $this->_sql);
    }

    public function getData(): array {
        return $this->_data;
    }

    public function format(string $sql = '', array $data = []): string {
        if ($sql === '') {
            $sql = $this->getSql();
        }
        if (count($data) === 0) {
            $data = $this->getData();
        }
        $i = -1;
        return preg_replace_callback('/\\?/', function () use (&$i, $data) {
            ++$i;
            if (isset($data[$i])) {
                return $this->quote($data[$i]);
            } else {
                return '\'\'';
            }
        }, $sql);
    }

    // --- 特殊方法 ---

    public function append(string $sql): Sql {
        $this->_sql[] = $sql;
        return $this;
    }

    public function quote($str): string {
        if (!is_string($str)) {
            return "'".$str."'";
        }
        if($this->_db) {
            return $this->_db->quote($str);
        } else {
            return "'" . addslashes($str) . "'";
        }
    }

    // --- 字段转义 ---
    public function field(string $str): string {
        $l = explode('.', $str);
        if (!isset($l[1])) {
            return '`' . $str . '`';
        }
        return '`' . $l[0] . '`.`' . $l[1] . '`';
    }

}

