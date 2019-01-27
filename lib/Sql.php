<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/6/24 18:55
 * Last: 2018-12-12 12:29:32
 */
declare(strict_types = 1);

namespace lib;

require ETC_PATH.'sql.php';

class Sql {

    // --- 组合成功的 sql ---
    private $_sql = [];
    private $_pre = '';
    private $_single = false;
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
    public function setSingle(bool $single): Sql {
        $this->_single = $single;
        return $this;
    }
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
                // --- 多条记录 ---
                if ($this->_single) {
                    // --- INSERT INTO xx (`id`, `name`) VALUES ('1', 'wow'), ('2', 'oh') ---
                    foreach ($vs as $is) {
                        $sql .= '(';
                        foreach ($is as $i => $v) {
                            $sql .= $this->quote($v.'') . ',';
                        }
                        $sql = substr($sql, 0, -1) . '),';
                    }
                    $sql = substr($sql, 0, -1);
                } else {
                    // --- INSERT INTO xx (id, name) VALUES (:p_id, :p_name) ---
                    $sql .= '(';
                    foreach ($vs[0] as $i => $v) {
                        $sql .= ':p_' . $cs[$i] . ',';
                    }
                    $sql = substr($sql, 0, -1) . ')';
                    foreach ($vs as $is) {
                        $line = [];
                        foreach ($is as $i => $v) {
                            $line[':p_'.$cs[$i]] = $v;
                        }
                        $this->_data[] = $line;
                    }
                }
            } else {
                // --- 单条记录 ---
                $sql .= '(';
                if ($this->_single) {
                    // --- INSERT INTO xx (id, name) VALUES ('1', 'wow') ---
                    foreach ($vs as $i => $v) {
                        $sql .= $this->quote($v.'') . ',';
                    }
                } else {
                    // --- INSERT INTO xx (id, name) VALUES (:p_id, :p_name) ---
                    foreach ($vs as $i => $v) {
                        $sql .= ':p_' . $cs[$i] . ',';
                        $this->_data[':p_' . $cs[$i]] = $v;
                    }
                }
                $sql = substr($sql, 0, -1) . ')';
            }
        } else {
            // --- 'xx', ['id' => '1', 'name' => 'wow'] ---
            $values = '';
            if ($this->_single) {
                // --- INSERT INTO xx (`id`, `name`) VALUES ('1', 'wow') ---
                foreach ($cs as $k => $v) {
                    $sql .= $this->field($k) . ',';
                    $values .= $this->quote($v.'') . ',';
                }
            } else {
                // --- INSERT INTO xx (id, name) VALUES (:p_id, :p_name) ---
                foreach ($cs as $k => $v) {
                    $sql .= $this->field($k) . ',';
                    $values .= ':p_' . $k . ',';
                    $this->_data[':p_' . $k] = $v;
                }
            }
            $sql = substr($sql, 0, -1) . ') VALUES (' . substr($values, 0, -1) . ')';
        }
        $this->_sql = [$sql];
        return $this;
    }

    // --- '*', 'xx' ---
    public function select(string $c, string $f): Sql {
        $this->_data = [];
        $sql = 'SELECT ';
        if (is_string($c)) $sql .= $c;
        else if (is_array($c)) {
            foreach ($c as $i) {
                $sql .= $i . ',';
            }
            $sql = substr($sql, 0, -1);
        }
        $sql .= ' FROM ' . $this->_pre . $f;
        $this->_sql = [$sql];
        return $this;
    }

    // --- 当不能 insert 时，update（仅能配合 insert 方法用） ---
    public function onDuplicate(array $s): Sql {
        if (count($s) > 0) {
            $sql = ' ON DUPLICATE KEY UPDATE '.$this->_updateSub($s);
            $this->_sql[] = $sql;
        }
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

    /**
     * --- 生成 xx = xx 的格式，或 xx = xx + 1 格式 ---
     * @param array $s 不可为空数组
     * @return string
     */
    private function _updateSub(array $s): string {
        $sql = '';
        if ($this->_single) {
            foreach ($s as $k => $v) {
                if (is_array($v)) {
                    // --- xx, [['total', '+', '1']] ---
                    $sql .= $this->field($v[0]) . ' = ' . $this->field($v[0]) . ' ' . $v[1] . ' ' . $this->quote($v[2].'') . ',';
                } else {
                    // --- xx, ['name' => 'oh'] ---
                    $sql .= $this->field($k) . ' = ' . $this->quote($v.'') . ',';
                }
            }
        } else {
            foreach ($s as $k => $v) {
                if (is_array($v)) {
                    $sql .= $this->field($v[0]) . ' = ' . $this->field($v[0]) . ' ' . $v[1] . ' :u_' . $v[0] . ',';
                    $this->_data[':u_'.$v[0]] = $v[2];
                } else {
                    $sql .= $this->field($k) . ' = :u_'.$k.',';
                    $this->_data[':u_'.$k] = $v;
                }
            }
        }
        $sql = substr($sql, 0, -1);
        return $sql;
    }

    // --- 'xx' ---
    public function delete(string $f): Sql {
        $this->_data = [];
        $this->_sql = ['DELETE ' . 'FROM ' . $this->_pre . $f];
        return $this;
    }

    /**
     * --- 筛选器 ---
     * --- 1.['city' => 'bj', 'type' => '2'] ---
     * --- 2.['city' => 'bj', ['type', '>', '1']] ---
     * --- 3.['city' => 'bj', ['type', 'in', ['1', '2']]] ---
     * --- 4.['city' => 'bj', 'type' => ['1', '2']] ---
     * --- 试验性 ---
     * --- 5.
     *  [
     *      'list' => [
     *          ['type', 'in', ['1', '2']]
     *      ]
     *  ],
     *  [
     *      'bound' => 'or',
     *      'list' => [
     *          ['type' => '5']
     *      ]
     * ] ---
     * @param array $s
     * @return Sql
     * @throws \Exception
     */
    public function where(array $s): Sql {
        if (count($s) > 0) {
            try {
                $sql = $this->_whereSub($s);
                $this->_sql[] = ' WHERE ' . $sql;
            } catch (\Exception $ex) {
                throw $ex;
            }
        }
        $this->_wsc = 0;
        return $this;
    }

    private $_wsc = 0;
    /**
     * --- 筛选器子过程 ---
     * @param array $s
     * @param string $type
     * @param int $lev 层级
     * @return string
     * @throws \Exception
     */
    private function _whereSub(array $s, string $type = 'AND', int $lev = 0): string {
        $sql = '';
        if (count($s) > 0) {
            foreach ($s as $k => $i) {
                if (is_string($k)) {
                    if (is_array($i)) {
                        // --- 4, IN ---
                        $sql .= 'AND ' . $this->field($k) . ' IN (';
                        if ($this->_single) {
                            foreach ($i as $v) {
                                $sql .= $this->quote($v.'') . ',';
                            }
                            $sql = substr($sql, 0, -1) . ') ';
                        } else {
                            foreach ($i as $k2 => $v) {
                                $sql .= ':w_'.$k . '_' . $k2.'_'.$this->_wsc . ',';
                                $this->_data[':w_'.$k . '_' . $k2.'_'.$this->_wsc] = $v;
                                ++$this->_wsc;
                            }
                            $sql = substr($sql, 0, -1) . ') ';
                        }
                    } else {
                        // --- 1 ---
                        if ($this->_single) {
                            $sql .= 'AND ' . $this->field($k) . ' = ' . $this->quote($i.'') . ' ';
                        } else {
                            $sql .= 'AND ' . $this->field($k) . ' = :w_' . $k.'_'.$this->_wsc . ' ';
                            $this->_data[':w_'.$k.'_'.$this->_wsc] = $i;
                            ++$this->_wsc;
                        }
                    }
                } else {
                    if (isset($i['list']) && is_array($i['list'])) {
                        // --- 5 ---
                        $bound = isset($i['bound']) ? strtoupper($i['bound']) : 'AND';
                        if ($bound == 'AND' || $bound == 'OR') {
                            $sql .= $this->_whereSub($i['list'], $bound, $lev + 1);
                        } else {
                            throw new \Exception('[Error] Bound not support.');
                        }
                    } else if (is_array($i[2])) {
                        // --- 3, IN ---
                        $sql .= 'AND ' . $this->field($i[0]) . ' '.$i[1].' (';
                        if ($this->_single) {
                            foreach ($i[2] as $v) {
                                $sql .= $this->quote($v.'') . ',';
                            }
                            $sql = substr($sql, 0, -1) . ') ';
                        } else {
                            foreach ($i[2] as $k2 => $v) {
                                $sql .= ':w_'.$i[0] . '_' . $k2.'_'.$this->_wsc . ',';
                                $this->_data[':w_'.$i[0] . '_' . $k2.'_'.$this->_wsc] = $v;
                                ++$this->_wsc;
                            }
                            $sql = substr($sql, 0, -1) . ') ';
                        }
                    } else {
                        // --- 2, > < = ---
                        if ($this->_single) {
                            $sql .= 'AND ' . $this->field($i[0]) . ' ' . $i[1] . ' ' . $this->quote($i[2].'') . ' ';
                        } else {
                            $sql .= 'AND ' . $this->field($i[0]) . ' ' . $i[1] . ' :w_' . $i[0].'_'.$this->_wsc . ' ';
                            $this->_data[':w_'.$i[0].'_'.$this->_wsc] = $i[2];
                            ++$this->_wsc;
                        }
                    }
                }
            }
            $sql = substr($sql, strpos($sql, ' ') + 1, -1);
            if ($lev > 0) {
                return $type . ' (' . $sql . ') ';
            } else {
                return $sql;
            }
        } else {
            return $sql;
        }
    }

    /**
     * @param string|array $c
     * @param string $d
     * @return Sql
     */
    public function by($c, string $d = 'DESC'): Sql {
        $sql = ' ORDER BY ';
        if (is_string($c)) {
            $sql .= $c . ' ' . $d;
        } else if (is_array($c)) {
            foreach ($c as $k => $v) {
                $sql .= $v . ',';
            }
            $sql = substr($sql, 0, -1) . ' ' . $d;
        }
        $this->_sql[] = $sql;
        return $this;
    }

    /**
     * @param string|array $c
     * @return Sql
     */
    public function groupBy($c): Sql {
        $sql = ' GROUP BY ';
        if (is_string($c)) {
            $sql .= $c;
        } else if (is_array($c)) {
            foreach ($c as $k => $v) {
                $sql .= $v . ',';
            }
            $sql = substr($sql, 0, -1);
        }
        $this->_sql[] = $sql;
        return $this;
    }

    public function limit(int $a, int $b): Sql {
        $this->_sql[] = ' LIMIT ' . $a . ', ' . $b;
        return $this;
    }

    // --- 操作 ---

    public function getSql(): string  {
        return implode('', $this->_sql);
    }

    public function getData(): array {
        return $this->_data;
    }

    // --- 特殊方法 ---

    public function append(string $sql): Sql {
        $this->_sql[] = $sql;
        return $this;
    }

    public function quote(string $str): string {
        if($this->_db) {
            return $this->_db->quote($str);
        } else {
            return "'" . addslashes($str) . "'";
        }
    }

    // --- 字段转义 ---
    public function field(string $str): string {
        return '`'.$str.'`';
    }

}

