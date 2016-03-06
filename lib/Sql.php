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
		'above' => ''
	];

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
		$this->sql['above'] = $sql;
		return $this;
	}

	function select($c, $f) {
		$sql = 'SELECT ';
		if(is_string($c)) $sql .= $c;
		else if(is_array($c)) {
			foreach($c as $i) $sql .= '`'.$i.'`, ';
			$sql = substr($sql, 0, -2);
		}
		$this->sql['above'] = $sql . ' FROM `'.DBPRE.$f.'`';
		return $this;
	}

	function update($f, $s = array()) {
		$sql = 'UPDATE `'.DBPRE.$f.'` SET ';
		foreach($s as $k => $i) {
			if(is_string($i) || is_numeric($i))
				$sql .= '`' . $k . '` = "' . $this->escape($i) . '",';
			else if(is_array($i)) {
				if($i[1] == '+' || $i[1] == '-')
					$sql .= '`' . $i[0] . '` = `' . $i[0] . '` ' . $i[1] . ' "' . $this->escape($i[2]) . '",';
			} else
				exit('Error, Sql, Update, '.gettype($i));
		}
		$this->sql['above'] = substr($sql, 0, -1);
		return $this;
	}

	function delete($f) {
		$this->sql['above'] = 'DELETE '.'FROM `'.DBPRE.$f.'`';
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
							$sql .= '`' . $i[0] . '` IN (';
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
			else {
				if(strtolower($v) == 'in') {
					$sql .= '`' . $s . '` IN (';
					foreach($v2 as $vv)
						$sql .= '"'.$this->escape($vv).'", ';
					$sql = substr($sql, 0, -2) . ') ';
				} else
					$sql .= '`' . $s . '` ' . $v . ' "' . $this->escape($v2) . '"';
			}
		}
		$this->sql['where'] = $sql;
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
		$this->sql['by'] = $sql;
		return $this;
	}

	function groupBy($c) {
		$sql = ' GROUP BY `'.$c.'`';
        $this->sql['groupBy'] = $sql;
		return $this;
	}

	function limit($a, $b) {
		$this->sql['limit'] = ' LIMIT '.$a.', '.$b;
		return $this;
	}

	// --- 特殊方法 ---

	function append($sql) {
		end($this->sql);
		$key = key($this->sql);
		$this->sql[$key] .= $sql;
		return $this;
	}

	function remove($key) {
		if(isset($this->sql[$key])) unset($this->sql[$key]);
		return $this;
	}

	function escape($str) {
		if(!isset(L()->Db))
			return addslashes($str);
		else
			return L()->Db->escape($str);
	}

	// --- 拼接 sql ---

	function get($hold = false) {
		$sql = $this->sql['above'];
		if(isset($this->sql['where'])) $sql .= $this->sql['where'];
        if(isset($this->sql['groupBy'])) $sql .= $this->sql['groupBy'];
		if(isset($this->sql['by'])) $sql .= $this->sql['by'];
		if(isset($this->sql['limit'])) $sql .= $this->sql['limit'];
		if(!$hold) {
			$this->sql = ['above' => ''];
		}
		return $sql;
	}

}

