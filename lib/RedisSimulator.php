<?php
/**
 * --- 注意了注意了 ---
 * 本模拟器基于 Db 类，不要用于任何实际运行环境。
 * 效率很低！且没意义！仅为方便测试不用装 Redis 环境。
 * 为什么不基于文件或者 SQLite？因为基于分布式的考量，要维护本地无状态。
 */

/*
CREATE TABLE `redis` (
`id` int(10) UNSIGNED NOT NULL,
  `tag` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `time_add` int(10) UNSIGNED NOT NULL,
  `time_exp` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `redis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tag` (`tag`);

ALTER TABLE `redis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
*/

/**
 * User: JianSuoQiYue
 * Date: 2017/09/29 15:26
 * Last: 2018-6-16 01:26
 */
declare(strict_types = 1);

namespace M\lib;

require ETC_PATH.'redis-simulator.php';

class RedisSimulator {

    /* @var $_link Db */
    private $_link = NULL;
    /* @var $_sql Sql */
    private $_sql = NULL;
    private $_index = 0;

    public function connect(string $host, int $port = 6379, float $timeout = 0.0, $reserved = null, int $retry_interval = 0): bool {
        // --- 模拟器要先获取 Db 类 ---
        if (Db::checkPool(RDS_DB)) {
            try {
                $this->_link = Db::get(RDS_DB);
                $this->_sql = Sql::get('__redisSimulator', RDS_DB_PRE === NULL ? SQL_PRE : RDS_DB_PRE);
                return true;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }
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
     * @param string $value
     * @param array $opt
     * @return bool
     */
    public function set(string $key, string $value, array $opt): bool {
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

    public function incr(string $key): bool {
        $this->_gc();
        try {
            $this->_sql->update('redis', [
                ['value', '+', '1']
            ])->where([
                'tag' => $this->_index . '_' . $key
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

    public function incrBy(string $key, int $value): bool {
        $this->_gc();
        try {
            $this->_sql->update('redis', [
                ['value', '+', $value]
            ])->where([
                'tag' => $this->_index . '_' . $key
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

    public function decr(string $key): bool {
        $this->_gc();
        try {
            $this->_sql->update('redis', [
                ['value', '-', '1']
            ])->where([
                'tag' => $this->_index . '_' . $key
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

    public function decrBy(string $key, int $value): bool {
        $this->_gc();
        try {
            $this->_sql->update('redis', [
                ['value', '-', $value]
            ])->where([
                'tag' => $this->_index . '_' . $key
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

