<?php

/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2015/7/14
 * Time: 18:47
 */
namespace Chameleon\Model;

class Model {

    protected $_primary = '';
    protected $_table = '';
    protected $_updates = [];
    protected $_updateConditions = [];

    public function set($n, $v, $condition = null) {
        // @todo: check for debug
        if (!isset($this->$n))
            throw new \Exception("Accessed an non-existent property.");
        if ($this->$n != $v) {
            $this->_updates[$n] = true;
            if ($condition !== null)
                $this->_updateConditions[$n] = $condition;
            return $this->$n = $v;
        }
        return $this->$n;
    }

    public function __set($n, $v) {
        // @todo: check for debug
        if (!isset($this->$n))
            throw new \Exception("Accessed an non-existent property.");
        if ($this->$n != $v) {
            $this->_updates[$n] = true;
            return $this->$n = $v;
        }
        return $this->$n;
    }

    public function __get($n){
        // @todo: check for debug
        if (!isset($this->$n))
            throw new \Exception("Accessed an non-existent property.");
        return $this->$n;
    }

    public function update() {
        $updates = [];
        foreach($this->_updates as $k => $v)
            $updates[$k] = $this->$k;
        if ($this->_updateConditions) {
            $this->_updateConditions[$this->_primary] = $this->{$this->_primary};
            $sql = L()->Sql->update($this->_table, $updates)->where($this->_updateConditions)->get();
        } else
            $sql = L()->Sql->update($this->_table, $updates)->where($this->_primary, $this->{$this->_primary})->get();
        if($r = L()->Db->query($sql, false)) {
            $this->_updates = [];
            $this->_updateConditions && ($this->_updateConditions = []);
            return $r;
        } else
            return false;
    }

    public function create() {
        $updates = [];
        foreach($this->_updates as $k => $v)
            $updates[$k] = $this->$k;
        $sql = L()->Sql->insert($this->_table, $updates)->get();
        if($r = L()->Db->query($sql)) {
            $this->{$this->_primary} = L()->Db->getInsertID();
            return $r;
        } else
            return false;

    }

}

trait ModelWithPKey {

    /**
     *   This method insert a new row into table with a non-numerical
     * primary key.
     * @return bool
     */
    public function create()
    {
        $updates = [];

        foreach ($this->_updates as $k => $v)
            $updates[$k] = $this->$k;

        do {
            $updates[$this->_primary] = $this->createPKey();
            $sql = L()->Sql->insert($this->_table, $updates)->get();
        } while (!($r = L()->Db->query($sql)) && L()->Db->getError() == 1062);

        if ($r) {
            $this->{$this->_primary} = $updates[$this->_primary];
            return true;
        }

        return false;
    }

    abstract public function createPKey();
}