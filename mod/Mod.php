<?php
/**
 * User: JianSuoQiYue
 * Last: 2018-6-17 21:03
 */
declare(strict_types = 1);

namespace M\Mod {

	use M\lib\Db;
	use M\lib\Sql;

    class Mod {
		
		// --- 可继承 ---
		protected static $_table = '';
		protected static $_primary = '';

        /* @var Db $__db */
        protected static $__db = NULL;
        /* @var string $__pre */
        protected static $__pre = NULL;

		// --- 其他方法 ---
        protected $_updates = [];
		/* @var Db $_db */
        protected $_db = NULL;
        /* @var Sql $_sql */
        protected static $_sql = NULL;
        /* @var string $_pre */
        protected $_pre = NULL;

        // --- 静态设置项 ---
        public static function setDb(Db $db) {
            self::$__db = $db;
        }
        public static function setPre(string $pre) {
            self::$__pre = $pre;
        }

		public function __construct(Db $db = NULL, string $pre = '') {
			$this->_db = $db;
            Mod::$_sql = Sql::get('__mod', $pre != '' ? $pre : (Mod::$__pre === NULL ? SQL_PRE : Mod::$__pre));
            $this->_pre = Mod::$_sql->getPre();
		}

		public function set($n, string $v = ''): void {
			if(is_array($n)) {
				foreach ($n as $k => $v) {
					// if ((isset($this->$k) && ($this->$k != $v)) || !isset($this->$k)) {
                    $this->_updates[$k] = true;
                    $this->$k = $v;
					// }
				}
			} else {
				if ((isset($this->$n) && ($this->$n != $v)) || !isset($this->$n)) {
					$this->_updates[$n] = true;
					$this->$n = $v;
				}
			}
		}

		// --- 更新 ---
		public function update(): bool {
			$updates = [];
			foreach ($this->_updates as $k => $v) {
                $updates[$k] = $this->$k;
            }

			if(count($updates) > 0) {
                Mod::$_sql->setPre($this->_pre);
			    try {
                    Mod::$_sql->update(static::$_table, $updates)->where([
                        static::$_primary => $this->{static::$_primary}
                    ]);
                    $ps = $this->_db->prepare(Mod::$_sql->getSql());
                    if ($ps->execute(Mod::$_sql->getData())) {
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
            Mod::$_sql->setPre($this->_pre);
            try {
                Mod::$_sql->delete(static::$_table)->where([
                    static::$_primary => $this->{static::$_primary}
                ]);
                $ps = $this->_db->prepare(Mod::$_sql->getSql());
                if ($ps->execute(Mod::$_sql->getData())) {
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

		// --- 静态方法 - 删除 ---

        /**
         * @param array|string $where
         * @param string $pre
         * @return bool
         */
		public static function delete($where, string $pre = ''): bool {
		    try {
                self::$_sql->setPre($pre != '' ? $pre : (self::$__pre === NULL ? SQL_PRE : self::$__pre));
                self::$_sql->delete(static::$_table)->where($where);
                if (is_array($where)) {
                    self::$_sql->where($where);
                } else {
                    self::$_sql->append(' WHERE ' . $where);
                }
                $ps = self::$__db->prepare(self::$_sql->getSql());
                if ($ps->execute(self::$_sql->getData())) {
                    if ($ps->rowCount() > 0) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (\Exception $e) {
		        \M\log($e->getMessage());
		        return false;
            }
		}

		public function create(): bool {
			$updates = [];
			foreach ($this->_updates as $k => $v) {
                $updates[$k] = $this->$k;
            }

            Mod::$_sql->setPre($this->_pre);
			Mod::$_sql->insert(static::$_table, $updates);
			$ps = $this->_db->prepare(Mod::$_sql->getSql());
			if ($ps->execute(Mod::$_sql->getData())) {
                $this->{static::$_primary} = $this->_db->getInsertID();
                // --- 重新获取 ---
                try {
                    Mod::$_sql->select('*', static::$_table)->where([
                        static::$_primary => $this->{static::$_primary}
                    ]);
                    $ps = $this->_db->prepare(Mod::$_sql->getSql());
                    $ps->execute(Mod::$_sql->getData());
                    $a = $ps->fetch(\PDO::FETCH_ASSOC);
                    foreach($a as $k => $v) {
                        $this->$k = $v;
                    }
                } catch (\Exception $e) {
                    \M\log($e->getMessage());
                    return false;
                }
                $this->_updates = [];
                return true;
            } else if ($this->_db->getErrorCode() == 1062) {
                return false;
            } else {
			    \M\log('[Db]' . print_r($this->_db->getErrorInfo(), true) . '(' . $this->_db->getErrorCode() . ')');
                return false;
            }
		}

		// --- 立即执行的自增 ---
		public function increase(string $col, int $num = 1): bool {
            Mod::$_sql->setPre($this->_pre);
            try {
                Mod::$_sql->update(static::$_table, [
                    [$col, '+', $num]
                ])->where([
                    static::$_primary => $this->{static::$_primary}
                ]);
                $ps = $this->_db->prepare(Mod::$_sql->getSql());
                if ($ps->execute(Mod::$_sql->getData())) {
                    if ($ps->rowCount() > 0) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } catch (\Exception $e) {
                \M\log($e->getMessage());
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
            Mod::$_sql->setPre($this->_pre);
            try {
                Mod::$_sql->update(static::$_table, [
                    'time_remove' => $_SERVER['REQUEST_TIME']
                ])->where([
                    static::$_primary => $this->{static::$_primary},
                    'time_remove' => '0'
                ]);
                $ps = $this->_db->prepare(Mod::$_sql->getSql());
                if ($ps->execute(Mod::$_sql->getData())) {
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
                \M\log($e->getMessage());
                return false;
            }

		}

		// --- 静态方法，获取对象 ---

        /**
         * @param array|string $where
         * @param bool $lock
         * @param string $pre
         * @return Mod|bool
         * @throws \Exception
         */
		public static function get($where, bool $lock = false, string $pre = '') {
			$mod = static::class;
            self::$_sql->setPre($pre != '' ? $pre : (self::$__pre === NULL ? SQL_PRE : self::$__pre));
			self::$_sql->select('*', static::$_table);
			if (is_array($where)) {
                self::$_sql->where($where);
            } else {
                self::$_sql->append(' WHERE '.$where);
            }
            if ($lock) {
                self::$_sql->append(' FOR UPDATE');
            }
            $ps = self::$__db->prepare(self::$_sql->getSql());
			if ($ps->execute(self::$_sql->getData())) {
                if ($obj = $ps->fetchObject($mod)) {
                    return $obj;
                } else {
                    return false;
                }
            } else {
			    return false;
            }
		}

		// --- 添加一个序列 ---
		public static function insert(array $cs, array $vs, string $pre = ''): bool {
		    self::$_sql->setPre($pre != '' ? $pre : (self::$__pre === NULL ? SQL_PRE : self::$__pre));
		    self::$_sql->insert(static::$_table, $cs, $vs);
		    $ps = self::$__db->prepare(self::$_sql->getSql());
		    if ($ps->execute(self::$_sql->getData())) {
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
        public static function getList(array $opt = [], string $pre = ''): array {
		    $opt['where'] = isset($opt['where']) ? $opt['where'] : NULL;
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : NULL;
            $opt['by'] = isset($opt['by']) ? $opt['by'] : NULL;
            $opt['array'] = isset($opt['array']) ? $opt['array'] : false;
            $opt['keyIsId'] = isset($opt['keyIsId']) ? $opt['keyIsId'] : false;
            $opt['lock'] = isset($opt['lock']) ? $opt['lock'] : false;

            $mod = static::class;
            self::$_sql->setPre($pre != '' ? $pre : (self::$__pre === NULL ? SQL_PRE : self::$__pre));
            self::$_sql->select('*', static::$_table);
            if ($opt['where'] !== NULL) {
                if (is_array($opt['where'])) {
                    try {
                        self::$_sql->where($opt['where']);
                    } catch (\Exception $e) {
                        \M\log($e->getMessage());
                        return [];
                    }
                } else {
                    self::$_sql->append(' WHERE ' . $opt['where']);
                }
            }
            if($opt['by'] !== NULL) {
                self::$_sql->by($opt['by'][0], $opt['by'][1]);
            }
            $total = NULL;
            if($opt['limit'] !== NULL) {
                if(isset($opt['limit'][2])) {
                    // --- 分页 ---
                    $sql = str_replace(' * ', ' COUNT(0) AS count ', self::$_sql->getSql());
                    $ps = self::$__db->prepare($sql);
                    $ps->execute(self::$_sql->getData());
                    $obj = $ps->fetch(\PDO::FETCH_ASSOC);
                    $total = $obj['count'];
                    // --- 计算完整 ---
                    self::$_sql->limit($opt['limit'][1] * ($opt['limit'][2] - 1), $opt['limit'][1]);
                } else {
                    self::$_sql->limit($opt['limit'][0], $opt['limit'][1]);
                }
            }
            if ($opt['lock']) {
                self::$_sql->append(' FOR UPDATE');
            }
            $ps = self::$__db->prepare(self::$_sql->getSql());
            $ps->execute(self::$_sql->getData());
            $list = [];
            if ($opt['array']) {
                while ($obj = $ps->fetch(\PDO::FETCH_ASSOC)) {
                    if ($opt['keyIsId']) {
                        $list[$obj['id']] = $obj;
                    } else {
                        $list[] = $obj;
                    }
                }
            } else {
                while ($obj = $ps->fetchObject($mod)) {
                    if ($opt['keyIsId']) {
                        $list[$obj->id] = $obj;
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
         * @param array|string $where
         * @param string $c
         * @param string $group
         * @param string $groupKey
         * @return array|int
         */
        public static function count($where, string $c = 'COUNT(0) AS count', string $group = '', string $groupKey = '') {
            self::$_sql->setPre(self::$__pre === NULL ? SQL_PRE : self::$__pre);
            self::$_sql->select($c, static::$_table);
            if(is_array($where)) {
                try {
                    self::$_sql->where($where);
                } catch (\Exception $e) {
                    \M\log($e->getMessage());
                    return 0;
                }
            } else {
                self::$_sql->append(' WHERE ' . $where);
            }
            if ($group != '') {
                self::$_sql->groupBy($group);
            }
            $ps = self::$__db->prepare(self::$_sql->getSql());
            $ps->execute(self::$_sql->getData());
            $obj = $ps->fetch(\PDO::FETCH_ASSOC);
            if ($c == 'COUNT(0) AS count') {
                return $obj['count'] + 0;
            } else {
                if ($group == '') {
                    return $obj;
                } else {
                    $list = [];
                    if ($groupKey == '') {
                        $list[] = $obj;
                        while ($obj = $ps->fetch(\PDO::FETCH_ASSOC)) {
                            $list[] = $obj;
                        }
                    } else {
                        $list[$obj[$groupKey]] = $obj;
                        while ($obj = $ps->fetch(\PDO::FETCH_ASSOC)) {
                            $list[$obj[$groupKey]] = $obj;
                        }
                    }
                    return $list;
                }
            }
        }

        // --- 满足条件则移除 ---

        /**
         * @param array|string $where
         * @return bool
         */
        public static function removeByWhere($where): bool {
            self::$_sql->setPre(self::$__pre === NULL ? SQL_PRE : self::$__pre);
            self::$_sql->delete(static::$_table);
            if(is_array($where)) {
                try {
                    self::$_sql->where($where);
                } catch (\Exception $e) {
                    \M\log($e->getMessage());
                    return false;
                }
            } else {
                self::$_sql->append(' WHERE ' . $where);
            }
            $ps = self::$__db->prepare(self::$_sql->getSql());
            if ($ps->execute(self::$_sql->getData())) {
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
            self::$_sql->setPre(self::$__pre === NULL ? SQL_PRE : self::$__pre);
            self::$_sql->update(static::$_table, $data);
            if(is_array($where)) {
                try {
                    self::$_sql->where($where);
                } catch (\Exception $e) {
                    \M\log($e->getMessage());
                    return false;
                }
            } else {
                self::$_sql->append(' WHERE ' . $where);
            }
            $ps = self::$__db->prepare(self::$_sql->getSql());
            if ($ps->execute(self::$_sql->getData())) {
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

    // --- 生成绝对单号 ---
	class ModKey extends Mod {

        protected static $_key = '';

        public function create(): bool {
            $updates = [];
            foreach ($this->_updates as $k => $v) {
                $updates[$k] = $this->$k;
            }

            Mod::$_sql->setPre($this->_pre);
            // --- 区别开始 ---
            $column = (static::$_key !== '') ? static::$_key : static::$_primary;
            do {
                $updates[$column] = $this->createKey();
                Mod::$_sql->insert(static::$_table, $updates);
                $ps = $this->_db->prepare(Mod::$_sql->getSql());
            } while (!($ps->execute(Mod::$_sql->getData())) && ($this->_db->getErrorCode() == 1062));
            if ($ps->rowCount() > 0) {
                $this->{$column} = $updates[$column];
                // --- 区别结束 ---
                // --- 重新获取 ---
                try {
                    Mod::$_sql->select('*', static::$_table)->where([
                        static::$_primary => $this->{static::$_primary}
                    ]);
                    $ps = $this->_db->prepare(Mod::$_sql->getSql());
                    $ps->execute(Mod::$_sql->getData());
                    $a = $ps->fetch(\PDO::FETCH_ASSOC);
                    foreach($a as $k => $v) {
                        $this->$k = $v;
                    }
                } catch (\Exception $e) {
                    \M\log($e->getMessage());
                    return false;
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

}

