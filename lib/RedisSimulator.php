<?php
/**
 * 注意了注意了
 * 本模拟器基于 Db 类
 * 不能运用于实际运行环境
 * 效率很低！且没意义！
 * 仅仅为了在本地方便测试不用装 Redis 环境
 */

namespace C\lib;

class RedisSimulator {

    private $_index = 0;

    public function connect($host, $port = 6379, $timeout = 0.0, $reserved = null, $retry_interval = 0) {
        // --- 呵呵，模拟器不需要连接 ---
        return true;
    }

    public function auth($password) {
        // --- 呵呵，模拟器也不需要验证 ---
        return true;
    }

    public function select($dbindex) {
        if(is_numeric($dbindex)) {
            $this->_index = $dbindex;
            return true;
        } else {
            return false;
        }
    }

    public function ping() {
        return "+PONG";
    }

    public function get($key) {
        $this->_gc();
        $ps = Db::query('SELECT * FROM '.DB_PRE.'redis WHERE tag = '.Db::quote($this->_index . '_' . $key).' AND `time_exp` >= '.$_SERVER['REQUEST_TIME']);
        if($obj = $ps->fetchObject()) {
            return $obj->value;
        } else
            return false;
    }

    public function set($key, $value, $opt) {
        $this->_gc();
        if($opt['ex'] == 0) {
            $time_exp = 4294967295;
        } else {
            $time_exp = $_SERVER['REQUEST_TIME'] + $opt['ex'];
        }
        if(isset($opt[0])) {
            if($opt[0] == 'nx') {
                if(Db::exec('INSERT INTO `'.DB_PRE.'redis`(`tag`, `value`, `time_add`, `time_exp`) VALUES (' . Db::quote($this->_index . '_' . $key).', '.Db::quote($value).',"'.$_SERVER['REQUEST_TIME'].'",'.Db::quote($time_exp).');')) {
                    return true;
                } else {
                    return false;
                }
            } else {
                if(Db::exec('UPDATE `'.DB_PRE.'redis` SET `value` = '.Db::quote($value).', `time_exp` = '.Db::quote($time_exp).' WHERE `tag` = '.Db::quote($this->_index . '_' . $key).';') != 0) {
                    return true;
                } else {
                    return false;
                }
            }
        } else {
            Db::exec('INSERT INTO `'.DB_PRE.'redis`(`tag`, `value`, `time_add`, `time_exp`) VALUES (' . Db::quote($this->_index . '_' . $key).', '.Db::quote($value).',"'.$_SERVER['REQUEST_TIME'].'",'.Db::quote($time_exp).') ON DUPLICATE KEY UPDATE `value` = '.Db::quote($value).', `time_exp` = '.Db::quote($time_exp).';');
            return true;
        }
    }

    public function incr($key) {
        $this->_gc();
        Db::exec(Db::exec('UPDATE `'.DB_PRE.'redis` SET `value` = `value` + 1 WHERE `tag` = '.Db::quote($this->_index . '_' . $key).';') != 0);
        return true;
    }

    public function incrBy($key, $value) {
        $this->_gc();
        Db::exec(Db::exec('UPDATE `'.DB_PRE.'redis` SET `value` = `value` + '.($value+0).' WHERE `tag` = '.Db::quote($this->_index . '_' . $key).';') != 0);
        return true;
    }

    public function decr($key) {
        $this->_gc();
        Db::exec(Db::exec('UPDATE `'.DB_PRE.'redis` SET `value` = `value` - 1 WHERE `tag` = '.Db::quote($this->_index . '_' . $key).';') != 0);
        return true;
    }

    public function decrBy($key, $value) {
        $this->_gc();
        Db::exec(Db::exec('UPDATE `'.DB_PRE.'redis` SET `value` = `value` - '.($value+0).' WHERE `tag` = '.Db::quote($this->_index . '_' . $key).';') != 0);
        return true;
    }

    private function _gc() {
        if(rand(0, 20) == 10) {
            Db::exec('DELETE FROM `'.DB_PRE.'redis` WHERE `time_exp` < '.$_SERVER['REQUEST_TIME'].';');
        }
    }

}

