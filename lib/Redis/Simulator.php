<?php
/**
 * --- 注意了注意了 ---
 * 本模拟器基于 Db 类，不要用于任何实际运行环境。
 * 效率很低！且没意义！仅为方便测试不用装 Redis 环境。
 * 为什么不基于文件或者 SQLite？因为基于分布式的考量，要维护本地无状态。
 */

/*
CREATE TABLE `redis` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `time_add` int(10) UNSIGNED NOT NULL,
  `time_exp` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

/**
 * User: JianSuoQiYue
 * Date: 2017/09/29 15:26
 * Last: 2018-6-16 01:26
 */
declare(strict_types = 1);

namespace lib\Redis;

use lib\Db;
use lib\Sql;

class Simulator {

    /* @var $_link Db */
    private $_link = NULL;
    /* @var $_sql Sql */
    private $_sql = NULL;
    private $_index = 0;

    // --- 非标准 设定 link ---
    public function __setDb(Db $db) {
        $this->_link = $db;
    }
    // --- 非标准 结束 ---

    public function connect(string $host, int $port = 6379, float $timeout = 0.0, $reserved = null, int $retry_interval = 0): bool {
        $this->_sql = Sql::get();
        return true;
    }

    public function auth(string $password): bool {
        // --- 呵呵，模拟器也不需要验证 ---
        return true;
    }

    public function select(int $dbindex): bool {
        if(is_numeric($dbindex)) {
            $this->_index = $dbindex;
            return true;
        } else {
            return false;
        }
    }

    public function ping(): string {
        return "+PONG";
    }

    public function get(string $key) {
        $this->_gc();
        try {
            $this->_sql->select('*', 'redis')->where([
                'tag' => $this->_index . '_' . $key,
                ['time_exp', '>=', $_SERVER['REQUEST_TIME']]
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            if ($ps->execute($this->_sql->getData())) {
                if ($obj = $ps->fetchObject()) {
                    return $obj->value;
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

    public function delete(string $key): bool {
        $this->_gc();
        try {
            $this->_sql->delete('redis')->where([
                'tag' => $this->_index . '_' . $key
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            if ($ps->execute($this->_sql->getData())) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param string $key
     * @param $value
     * @param array $opt
     * @return bool
     */
    public function set(string $key, $value, array $opt): bool {
        $this->_gc();
        if($opt['ex'] == 0) {
            $time_exp = 4294967295;
        } else {
            $time_exp = $_SERVER['REQUEST_TIME'] + $opt['ex'];
        }
        if(isset($opt[0])) {
            if($opt[0] == 'nx') {
                try {
                    // --- 强制清除过期数据 ---
                    $this->_sql->delete('redis')->where([
                        ['time_exp', '<', $_SERVER['REQUEST_TIME']]
                    ]);
                    $ps = $this->_link->prepare($this->_sql->getSql());
                    $ps->execute($this->_sql->getData());
                    // --- 不存在才建立 ---
                    $this->_sql->insert('redis', [
                        'tag' => $this->_index . '_' . $key,
                        'value' => $value,
                        'time_add' => $_SERVER['REQUEST_TIME'],
                        'time_exp' => $time_exp
                    ]);
                    $ps = $this->_link->prepare($this->_sql->getSql());
                    if ($ps->execute($this->_sql->getData())) {
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
            } else {
                // --- xx, 存在才修改 ---
                try {
                    $this->_sql->update('redis', [
                        'value' => $value,
                        'time_exp' => $time_exp
                    ])->where([
                        'tag' => $this->_index . '_' . $key,
                        ['time_exp', '>=', $_SERVER['REQUEST_TIME']]
                    ]);
                    $ps = $this->_link->prepare($this->_sql->getSql());
                    if ($ps->execute($this->_sql->getData())) {
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
        } else {
            $this->_sql->insert('redis', [
                'tag' => $this->_index . '_' . $key,
                'value' => $value,
                'time_add' => $_SERVER['REQUEST_TIME'],
                'time_exp' => $time_exp
            ])->append(' ON DUPLICATE KEY UPDATE `value` = '.$this->_link->quote($value).', `time_exp` = '.$this->_link->quote(''.$time_exp));
            $ps = $this->_link->prepare($this->_sql->getSql());
            if ($ps->execute($this->_sql->getData())) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function incr(string $key): int {
        return $this->_incrDecr($key, 1, '+');
    }
    public function incrBy(string $key, int $value): int {
        return $this->_incrDecr($key, $value, '+');
    }
    public function decr(string $key): int {
        return $this->_incrDecr($key, 1, '-');
    }
    public function decrBy(string $key, int $value): int {
        return $this->_incrDecr($key, $value, '-');
    }
    private function _incrDecr(string $key, int $value, string $op = '+'): int {
        $this->_gc();
        try {
            $this->_sql->update('redis', [
                ['value', $op, $value]
            ])->where([
                'tag' => $this->_index . '_' . $key
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            if ($ps->execute($this->_sql->getData())) {
                if ($ps->rowCount() > 0) {
                    return 0;
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function _gc(): void {
        if(rand(0, 20) == 10) {
            try {
                $this->_sql->delete('redis')->where([
                    ['time_exp', '<', $_SERVER['REQUEST_TIME']]
                ]);
                $ps = $this->_link->prepare($this->_sql->getSql());
                $ps->execute($this->_sql->getData());
            } catch (\Exception $e) {

            }
        }
    }

}

