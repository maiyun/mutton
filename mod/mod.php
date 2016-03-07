<?php

namespace C {

	class mod {

		protected $__primary = '';
		protected $__table = '';
		protected $__updates = [];

		public function set($n, $v) {
			if ($this->$n != $v) {
				$this->_updates[$n] = true;
				$this->$n = $v;
			}
		}

		public function update() {
			$updates = [];
			foreach ($this->_updates as $k => $v)
				$updates[$k] = $this->$k;
			$sql = L()->Sql->update($this->_table, $updates)->where($this->_primary, $this->{$this->_primary})->get();
			if ($r = L()->Db->query($sql, false)) {
				$this->_updates = [];
				return $r;
			} else
				return false;
		}

		public function create() {
			$updates = [];
			foreach ($this->_updates as $k => $v)
				$updates[$k] = $this->$k;
			$sql = L()->Sql->insert($this->_table, $updates)->get();
			if ($r = L()->Db->query($sql, false)) {
				$this->{$this->_primary} = L()->Db->getInsertID();
				return $r;
			} else if (L()->Db->getErrno() == 1062)
				return false;
			else {
				echo '[Db]' . L()->Db->getError() . '(' . L()->Db->getErrno() . ')';
				return false;
			}

		}

		public function toArray() {
			$rtn = [];
			foreach ($this as $key => $v)
				if ($key[0] != '_')
					$rtn[$key] = $v;
			return $rtn;
		}

		/**
		 * 需要数据库支持 is_remove、time_remove 字段
		 */
		public function softRemove() {
			L()->Db->query(L()->Sql->update($this->_table, ['is_remove' => '1', 'time_remove' => $_SERVER['REQUEST_TIME']])->where([$this->_primary => $this->{$this->_primary}, 'is_remove' => '0'])->get());
			if (L()->Db->getAffectRows() > 0) {
				$this->is_remove = '1';
				$this->time_remove = $_SERVER['REQUEST_TIME'];
				return true;
			} else return false;
		}

	}

	trait modPKey {

		/**
		 * This method insert a new row into table with a non-numerical
		 * primary key.
		 * @return bool
		 */
		public function create() {
			$updates = [];

			foreach ($this->_updates as $k => $v)
				$updates[$k] = $this->$k;

			do {
				$updates[$this->_primary] = $this->createPKey();
				$sql = L()->Sql->insert($this->_table, $updates)->get();
			} while (!($r = L()->Db->query($sql, false)) && L()->Db->getError() == 1062);

			if ($r) {
				$this->{$this->_primary} = $updates[$this->_primary];
				return true;
			}

			return false;
		}

		abstract public function createPKey();
	}

}

