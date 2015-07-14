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

    public function __set($n, $v) {
        if ($this->$n != $v) {
            $this->_updates[$n] = true;
            return $this->$n = $v;
        }
        return $this->$n;
    }

    public function update() {
        $updates = [];
        foreach($this->_updates as $k => $v)
            $updates[$k] = $this->$k;
        $primary = $this->_primary;
        if($r = L()->Db->query(L()->Sql->update($this->_table, $updates)->where($this->_primary, $this->$primary)->get())) {
            $this->_updates = [];
            return $r;
        } else
            return false;
    }

}