<?php
/**
 * --- 注意了注意了 ---
 * 本模拟器基于 Db 类，尽量不要用于任何实际运行环境。
 * 效率低，且没意义，仅为方便测试不用装 Redis 环境。
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
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2017/09/29 15:26
 * Last: 2018-6-16 01:26, 2019-12-27 17:15:29, 2020-01-05 00:50:07
 */
declare(strict_types = 1);

namespace lib\Kv;

use lib\Db;
use lib\LSql;
use lib\Sql;

class RedisSimulator implements IKv {

    /* @var $_link Db */
    private $_link = null;

    /** @var string key 的前置 */
    private $_pre = '';

    /* @var $_sql LSql 类 */
    private $_sql = null;
    /** @var string 表名 */
    private $_table = 'redis';

    /** @var array 当前连接的 redis 服务器信息（兼容 Memcached） */
    private $_serverList = [];
    /** @var int 最后一次执行返回的 code（兼容 Memcached） */
    private $_resultCode = 0;
    /** @var string 最后一次执行返回的说明（兼容 Memcached） */
    private $_resultMessage = 'SUCCESS';

    /** @var int 模拟当前 db index（兼容 Redis） */
    private $_index = 0;

    /**
     * @param array $opt db, sqlPre
     * @return bool|null
     */
    public function connect(array $opt = []) {
        if (!isset($opt['db'])) {
            return false;
        }
        $this->_link = $opt['db'];
        $etc = null;
        if (isset($opt['sqlPre'])) {
            $etc = [
                'pre' => $opt['sqlPre']
            ];
        }
        if (isset($opt['table'])) {
            $this->_table = $opt['table'];
        }
        $this->_sql = Sql::get($etc);
        return true;
    }

    /**
     * --- 判断是否连接成功 ---
     * @return bool
     */
    public function isConnect(): bool {
        if ($this->_link === null) {
            return false;
        }
        return $this->_link->isConnected();
    }

    /**
     * --- 退出断开连接 ---
     */
    public function quit(): void {
        // --- 无需退出 ---
    }

    /**
     * --- 设定一个值 ---
     * @param string $key
     * @param mixed $val
     * @param int $ttl 秒，0 为不限制
     * @param string $mod 设置模式: 空,nx（key不存在才建立）,xx（key存在才修改）
     * @return bool
     */
    public function set(string $key, $val, int $ttl = 0, string $mod = '') {
        $this->_gc();
        $time = time();
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_array($val)) {
            $val = json_encode($val);
        }
        if ($ttl == 0) {
            $time_exp = 4294967295;
        } else {
            $time_exp = time() + $ttl;
        }
        if ($mod === 'nx') {
            // --- 强制清除过期数据，防止一些问题 ---
            $this->_sql->delete($this->_table)->where([
                ['time_exp', '<', $time]
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            $ps->execute($this->_sql->getData());
            // --- 不存在才建立 ---
            $this->_sql->insert('redis', [
                'tag' => $this->_index . '_' . $key,
                'value' => $val,
                'time_add' => $_SERVER['REQUEST_TIME'],
                'time_exp' => $time_exp
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            if ($ps->execute($this->_sql->getData())) {
                if ($ps->rowCount() > 0) {
                    return true;
                } else {
                    $this->_resultCode = -1;
                    $this->_resultMessage = 'Key already exists.';
                    return false;
                }
            } else {
                $this->_resultCode = -1;
                $this->_resultMessage = $this->_link->getErrorInfo()[0];
                return false;
            }
        } else if ($mod === 'xx') {
                // --- xx, 存在才修改 ---
                $this->_sql->update($this->_table, [
                    'value' => $val,
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
                        $this->_resultCode = -1;
                        $this->_resultMessage = 'Key does not exist.';
                        return false;
                    }
                } else {
                    $this->_resultCode = -1;
                    $this->_resultMessage = $this->_link->getErrorInfo()[0];
                    return false;
                }
        } else {
            $this->_sql->insert($this->_table, [
                'tag' => $this->_index . '_' . $key,
                'value' => $val,
                'time_add' => $time,
                'time_exp' => $time_exp
            ])->append(' ON DUPLICATE KEY UPDATE `value` = ' . $this->_link->quote($val) . ', `time_exp` = ' . $this->_link->quote((string)$time_exp));
            $ps = $this->_link->prepare($this->_sql->getSql());
            if ($ps->execute($this->_sql->getData())) {
                return true;
            } else {
                $this->_resultCode = -1;
                $this->_resultMessage = $this->_link->getErrorInfo()[0];
                return false;
            }
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

    public function delete($key): bool {
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

