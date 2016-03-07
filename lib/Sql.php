<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/6/24
 * Time: 18:55
 */

namespace C\lib {

	class Sql {

		var $sql = '';

		var $pre = NULL;
		var $lib = NULL;

		// --- 实例化 ---
		public function __construct() {
			$this->pre = DB_PRE;
			$this->lib = DB_LIB;
		}

		// --- 前导 ---

		public function insert($f, $cs = array(), $vs = array()) {
			$this->sql = 'INSERT' . ' INTO ';
			if (is_string($f)) $this->sql .= '`' . $this->pre . $f . '` (';
			if (count($vs) > 0) {
				// --- "shop", ['name', 'address'], ['猎云酒店', '云之路29号'] ---
				foreach ($cs as $i)
					$this->sql .= '`' . $i . '`,';
				$this->sql = substr($this->sql, 0, -1) . ') VALUES ';
				// --- 判断插入单条记录还是多条记录 ---
				if (is_array($vs[0])) {
					foreach ($vs as $is) {
						$this->sql .= '(';
						foreach ($is as $i)
							$this->sql .= '"' . $this->escape($i) . '",';
						$this->sql = substr($this->sql, 0, -1) . '), ';
					}
					$this->sql = substr($this->sql, 0, -2);
				} else {
					$this->sql .= '(';
					foreach ($vs as $i)
						$this->sql .= '"' . $this->escape($i) . '",';
					$this->sql = substr($this->sql, 0, -1) . ')';
				}
			} else {
				// --- "shop", ['name' => '猎云酒店', 'address' => '云之路29号'] ---
				$values = '';
				foreach ($cs as $key => $val) {
					$this->sql .= '`' . $key . '`,';
					$values .= '"' . $this->escape($val) . '",';
				}
				$this->sql = substr($this->sql, 0, -1) . ') VALUES (' . substr($values, 0, -1) . ')';
			}
			return $this;
		}

		public function select($c, $f) {
			$this->sql = 'SELECT ';
			if (is_string($c)) $this->sql .= $c;
			else if (is_array($c)) {
				foreach ($c as $i) $this->sql .= '`' . $i . '`, ';
				$this->sql = substr($this->sql, 0, -2);
			}
			$this->sql .= ' FROM `' . $this->pre . $f . '`';
			return $this;
		}

		function update($f, $s = array()) {
			$this->sql = 'UPDATE `' . $this->pre . $f . '` SET ';
			foreach ($s as $k => $i) {
				if (is_string($i) || is_numeric($i))
					$this->sql .= '`' . $k . '` = "' . $this->escape($i) . '",';
				else if (is_array($i)) {
					if ($i[1] == '+' || $i[1] == '-')
						$this->sql .= '`' . $i[0] . '` = `' . $i[0] . '` ' . $i[1] . ' "' . $this->escape($i[2]) . '",';
				} else
					throw new \Exception('Error, Sql, Update, ' . gettype($i));
			}
			$this->sql = substr($this->sql, 0, -1);
			return $this;
		}

		function delete($f) {
			$this->sql = 'DELETE ' . 'FROM `' . $this->pre . $f . '`';
			return $this;
		}

		// --- 筛选器 ---

		function where($s, $v = '', $v2 = '') {
			$sql = '';
			if (is_array($s)) {
				if (count($s)) {
					$sql = ' WHERE ';
					foreach ($s as $k => $i) {
						if (is_string($i) || is_numeric($i)) {
							if (substr($i, 0, 1) == '`')
								$sql .= $i . ' AND ';
							else
								$sql .= '`' . $k . '` = "' . $this->escape($i) . '" AND ';
						} else if (is_array($i)) {
							if (strtolower($i[1]) == 'in') {
								$sql .= '`' . $i[0] . '` IN (';
								foreach ($i[2] as $v)
									$sql .= '"' . $this->escape($v) . '", ';
								$sql = substr($sql, 0, -2) . ') AND ';
							} else
								$sql .= '`' . $i[0] . '` ' . $i[1] . ' "' . $this->escape($i[2]) . '" AND ';
						} else
							throw new \Exception('[MyX - L(Mysql) Error] only support string or array, but yours type is ' . gettype($i));
					}
					$sql = substr($sql, 0, -5);
				}
			} else if (is_string($s)) {
				$sql = ' WHERE ';
				if ($v2 == '')
					$sql .= '`' . $s . '` = "' . $this->escape($v) . '" ';
				else {
					if (strtolower($v) == 'in') {
						$sql .= '`' . $s . '` IN (';
						foreach ($v2 as $vv)
							$sql .= '"' . $this->escape($vv) . '", ';
						$sql = substr($sql, 0, -2) . ') ';
					} else
						$sql .= '`' . $s . '` ' . $v . ' "' . $this->escape($v2) . '"';
				}
			}
			$this->sql .= $sql;
			return $this;
		}

		function by($c, $d = 'DESC') {
			$sql = ' ORDER BY ';
			if (is_string($c)) $sql .= '`' . $c . '` ' . $d;
			else if (is_array($c)) {
				foreach ($c as $k => $v) {
					$sql .= '`' . $v . '`,';
				}
				$sql = substr($sql, 0, -1) . ' ' . $d;
			}
			$this->sql .= $sql;
			return $this;
		}

		function groupBy($c) {
			$this->sql .= ' GROUP BY `' . $c . '`';
			return $this;
		}

		function limit($a, $b) {
			$this->sql .= ' LIMIT ' . $a . ', ' . $b;
			return $this;
		}

		// --- 特殊方法 ---

		function append($sql) {
			$this->sql .= $sql;
			return $this;
		}

		// --- 此方法暂时作废 ---
		function remove($key) {
			//if (isset($this->sql[$key])) unset($this->sql[$key]);
			return $this;
		}

		function escape($str) {
			if ($this->lib == 'Mysql')
				return Mysql::escape($str);
			else
				return addslashes($str);
		}

	}

}

