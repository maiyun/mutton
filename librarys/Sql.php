<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/6/24
 * Time: 18:55
 */

namespace Chameleon\Library;

class Sql {

    var $sql = [
        '0' => [
            'above' => ''
        ]
    ];

    var $on = '0';
    var $db = NULL;

    // --- 可以随时切换多个 SQL 对象 ---

    function on($key = '0') {

        if (!isset($this->sql[$key])) $this->sql[$key] = ['above' => ''];
        $this->on = $key;
        return $this;

    }

    // --- above 类 ---

    function insert($f, $cs = array(), $vs = array()) {
        $sql = 'INSERT'.' INTO ';
        if(is_string($f)) $sql .= '`'.DBPRE.$f.'` (';
        if(count($vs) > 0) {
            // --- "shop", ['name', 'address'], ['猎云酒店', '云之路29号'] ---
            foreach ($cs as $i) {
                $sql .= '`' . $i . '`,';
            }
            $sql = substr($sql, 0, -1) . ') VALUES ';
            // --- 判断插入单条记录还是多条记录 ---
            if (is_array($vs[0])) {
                foreach ($vs as $is) {
                    $sql .= '(';
                    foreach ($is as $i)
                        $sql .= '"' . $this->escape($i) . '",';
                    $sql = substr($sql, 0, -1) . '), ';
                }
                $sql = substr($sql, 0, -2);
            } else {
                $sql .= '(';
                foreach ($vs as $i)
                    $sql .= '"' . $this->escape($i) . '",';
                $sql = substr($sql, 0, -1) . ')';
            }
        } else {
            // --- "shop", ['name' => '猎云酒店', 'address' => '云之路29号'] ---
            $values = '';
            foreach($cs as $key => $val) {
                $sql .= '`'.$key.'`,';
                $values .= '"' . $this->escape($val) . '",';
            }
            $sql = substr($sql, 0, -1) . ') VALUES (' . substr($values, 0, -1) . ')';
        }
        $this->sql[$this->on]['above'] = $sql;
        return $this;
    }

    function select($c, $f) {
        $sql = 'SELECT ';
        if(is_string($c)) $sql .= $c;
        else if(is_array($c)) {
            foreach($c as $i) $sql .= '`'.$i.'`, ';
            $sql = substr($sql, 0, -2);
        }
        $this->sql[$this->on]['above'] = $sql . ' FROM `'.DBPRE.$f.'`';
        return $this;
    }

    function update($f, $s = array()) {
        $sql = 'UPDATE `'.DBPRE.$f.'` SET ';
        foreach($s as $k => $i) {
            $sql .= '`' . $k . '` = "' . $this->escape($i) . '",';
        }
        $this->sql[$this->on]['above'] = substr($sql, 0, -1);
        return $this;
    }

    function delete($f) {
        $this->sql[$this->on]['above'] = 'DELETE '.'FROM `'.DBPRE.$f.'`';
        return $this;
    }

    // --- 筛选器 ---

    function where($s, $v = '', $v2 = '') {
        $sql = '';
        if(is_array($s)) {
            if(count($s)) {
                $sql = ' WHERE ';
                foreach ($s as $k => $i) {
                    if (is_string($i) || is_numeric($i)) {
                        if(substr($i, 0, 1) == '`')
                            $sql .= $i . ' AND ';
                        else
                            $sql .= '`' . $k . '` = "' . $this->escape($i) . '" AND ';
                    } else if (is_array($i)) {
                        if(strtolower($i[1]) == 'in') {
                            $sql .= '`' . $i[0] . '` IN (' . $this->escape($i[2]);
                            foreach($i[2] as $v)
                                $sql .= '"'.$this->escape($v).'", ';
                            $sql = substr($sql, 0, -2) . ') AND ';
                        } else
                            $sql .= '`' . $i[0] . '` ' . $i[1] . ' "' . $this->escape($i[2]) . '" AND ';
                    } else exit('[MyX - L(Mysql) Error] only support string or array, but yours type is ' . gettype($i));
                }
                $sql = substr($sql, 0, -5);
            }
        } else if(is_string($s)) {
            $sql = ' WHERE ';
            if($v2 == '')
                $sql .= '`' . $s . '` = "' . $this->escape($v) . '" ';
            else
                $sql .= '`' . $s . '` '.$v.' "' . $this->escape($v2) . '" ';
        }
        $this->sql[$this->on]['where'] = $sql;
        return $this;
    }

    function by($c, $d = 'DESC') {
        $sql = ' ORDER BY ';
        if(is_string($c)) $sql .= '`' . $c . '` '.$d;
        else if(is_array($c)) {
            foreach($c as $k => $v) {
                $sql .= '`'.$v.'`,';
            }
            $sql = substr($sql, 0, -1) . ' '.$d;
        }
        $this->sql[$this->on]['by'] = $sql;
        return $this;
    }

    function limit($a, $b) {
        $this->sql[$this->on]['limit'] = ' LIMIT '.$a.', '.$b;
        return $this;
    }

    // --- 特殊方法 ---

    function append($sql) {
        end($this->sql[$this->on]);
        $key = key($this->sql[$this->on]);
        $this->sql[$this->on][$key] .= $sql;
        return $this;
    }

    function remove($key) {
        if(isset($this->sql[$this->on][$key])) unset($this->sql[$this->on][$key]);
        return $this;
    }

    function escape($str) {
        if($this->db === NULL)
            return addslashes($str);
        else
            return $this->db->escape($str);
    }

    // --- 拼接 sql ---

    function get($hold = false) {
        $sql = $this->sql[$this->on]['above'];
        if(isset($this->sql[$this->on]['where'])) $sql .= $this->sql[$this->on]['where'];
        if(isset($this->sql[$this->on]['by'])) $sql .= $this->sql[$this->on]['by'];
        if(isset($this->sql[$this->on]['limit'])) $sql .= $this->sql[$this->on]['limit'];
        if(!$hold) {
            if($this->on=='0') $this->sql[$this->on] = ['above' => ''];
            else unset($this->sql[$this->on]);
        }
        return $sql;
    }

}