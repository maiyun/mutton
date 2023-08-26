<?php
/**
 * --- 注意了注意了 ---
 * 本模拟器基于 Db 类，尽量不要用于任何实际运行环境。
 * 效率低意义不大，仅为没有 Redis 环境测试使用。
 */

/*
CREATE TABLE `redis` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `time_add` bigint NOT NULL,
  `time_exp` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2017/09/29 15:26
 * Last: 2018-6-16 01:26, 2019-12-27 17:15:29, 2020-01-05 00:50:07, 2020-1-28 15:06:46, 2020-2-19 10:04:47, 2020-3-28 13:21:16, 2022-3-25 00:44:31, 2022-08-31 15:20:35, 2022-09-21 12:01:02
 */
declare(strict_types = 1);

namespace lib\Kv;

use lib\Db;
use lib\Sql;
use lib\LSql;
use PDO;
use PDOException;

class RedisSimulator implements IKv {

    /** @var Db */
    private $_link = null;

    /** @var string key 的前置 */
    private $_pre = '';

    /** @var LSql sql 类 */
    private $_sql = null;

    /** @var string 模拟器的数据库表名 */
    private $_table = 'redis';

    /** @var string|null 最后一次错误信息 */
    private $_lastError = null;

    /** @var int 模拟当前 db index */
    private $_index = 0;

    /**
     * @param array $opt db, sqlPre, table
     * @return bool|null
     */
    public function connect(array $opt = []) {
        $host =  isset($opt['host']) ? $opt['host'] : RD_HOST;
        $port = isset($opt['port']) ? $opt['port'] : RD_PORT;
        // $user = isset($opt['user']) ? $opt['user'] : RD_USER;
        // $pwd = isset($opt['pwd']) ? $opt['pwd'] : RD_PWD;
        $index = isset($opt['index']) ? $opt['index'] : RD_INDEX;
        $this->_pre = isset($opt['pre']) ? $opt['pre'] : RD_PRE;
        /** @var Db $db */
        $db = isset($opt['db']) ? $opt['db'] : null;

        if (!$db) {
            return false;
        }

        $this->_link = $opt['db'];
        $this->_index = $index;

        $sqlPre = isset($opt['sqlPre']) ? $opt['sqlPre'] : null;
        $this->_sql = Sql::get($sqlPre);
        if (isset($opt['table'])) {
            $this->_table = $opt['table'];
        }

        return true;
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
        $time = time();
        if (is_array($val)) {
            $val = json_encode($val);
        }
        if ($ttl == 0) {
            $time_exp = 4294967295;
        }
        else {
            $time_exp = time() + $ttl;
        }
        // --- 空, nx（不存在才建立）,xx（存在才修改）是不同的情况 ---
        if ($mod === 'nx') {
            $this->_gcDo();
            // --- 不存在才建立 ---
            $this->_sql->insert($this->_table)->values([
                'tag' => $this->_index . '_' . $this->_pre . $key,
                'value' => $val,
                'time_add' => $_SERVER['REQUEST_TIME'],
                'time_exp' => $time_exp
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            try {
                $ps->execute($this->_sql->getData());
                return true;
            }
            catch (PDOException $e) {
                $this->_lastError = $e->errorInfo[2];
                return false;
            }
        }
        else if ($mod === 'xx') {
            $this->_gcDo();
            // --- xx, 存在才修改 ---
            $this->_sql->update($this->_table, [
                'value' => $val,
                'time_exp' => $time_exp
            ])->where([
                'tag' => $this->_index . '_' . $this->_pre . $key,
                ['time_exp', '>', $time]
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            try {
                $ps->execute($this->_sql->getData());
                if ($ps->rowCount() > 0) {
                    return true;
                }
                else {
                    $this->_lastError = 'Key does not exist.';
                    return false;
                }
            }
            catch (PDOException $e) {
                $this->_lastError = $e->errorInfo[2];
                return false;
            }
        }
        else {
            $this->_gc();
            $this->_sql->insert($this->_table)->values([
                'tag' => $this->_index . '_' . $this->_pre . $key,
                'value' => $val,
                'time_add' => $time,
                'time_exp' => $time_exp
            ])->duplicate([
                'value' => $val,
                'time_exp' => $time_exp
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            try {
                $ps->execute($this->_sql->getData());
                return true;
            }
            catch (PDOException $e) {
                $this->_lastError = $e->errorInfo[2];
                return false;
            }
        }
    }

    /**
     * --- 添加一个值，存在则不变 ---
     * @param string $key
     * @param $val
     * @param int $ttl 有效期
     * @return bool
     */
    public function add(string $key, $val, int $ttl = 0): bool {
        return $this->set($key, $val, $ttl, 'nx');
    }

    /**
     * --- 替换一个存在的值 ---
     * @param string $key
     * @param $val
     * @param int $ttl
     * @return bool
     */
    public function replace(string $key, $val, int $ttl = 0) {
        return $this->set($key, $val, $ttl, 'xx');
    }

    /**
     * --- 向已存在的值后追加数据 ---
     * @param string $key
     * @param $val
     * @return bool
     */
    public function append(string $key, $val) {
        $this->_gc();
        $this->_sql->insert($this->_table)->values([
            'tag' => $this->_index . '_' . $this->_pre . $key,
            'value' => $val,
            'time_add' => time(),
            'time_exp' => 4294967295
        ])->duplicate([
            'value' => ['CONCAT(`value`, ?)', [$val]]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            return true;
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
    }

    /**
     * --- 向已存在的值之前追加数据 ---
     * @param string $key
     * @param $val
     * @return bool
     */
    public function prepend(string $key, $val) {
        $this->_gc();
        $this->_sql->update($this->_table, [
            'value' => ['CONCAT(?, `value`)', [$val]]
        ])->where([
            'tag' => $this->_index . '_' . $this->_pre . $key,
            ['time_exp', '>', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            if ($ps->rowCount() > 0) {
                return true;
            }
            else {
                $this->_lastError = 'Key does not exist.';
                return false;
            }
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
    }

    /**
     * --- 检测 key 是否存在 ---
     * @param string[]|string $key 单个或序列
     * @return int
     */
    public function exists($key) {
        $this->_gc();
        if (is_string($key)) {
            $key = [$key];
        }
        foreach ($key as $k => $v) {
            $key[$k] = $this->_index . '_' . $this->_pre . $v;
        }
        $this->_sql->select(['tag'], $this->_table)->where([
            'tag' => $key,
            ['time_exp', '>', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            $i = 0;
            while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                if (in_array($row['tag'], $key)) {
                    ++$i;
                }
            }
            return $i;
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return 0;
        }
    }

    /**
     * --- 获取数值和字符串 ---
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key) {
        $this->_gc();
        $this->_sql->select('*', $this->_table)->where([
            'tag' => $this->_index . '_' . $this->_pre . $key,
            ['time_exp', '>', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            if ($obj = $ps->fetchObject()) {
                return $obj->value;
            }
            else {
                $this->_lastError = $ps->errorInfo()[2];
                return null;
            }
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return null;
        }
    }

    /**
     * --- 获取相应的剩余有效期秒数 ---
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key) {
        $this->_gc();
        $this->_sql->select('*', $this->_table)->where([
            'tag' => $this->_index . '_' . $this->_pre . $key,
            ['time_exp', '>', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            if ($obj = $ps->fetchObject()) {
                if ((int)$obj->time_exp === 4294967295) {
                    return -1;
                }
                $time = time();
                return $obj->time_exp - $time;
            }
            else {
                return null;
            }
        }
        catch (PDOException $e) {
            return null;
        }
    }

    /**
     * --- 获取相应的剩余有效期毫秒数 ---
     * @param string $key
     * @return int|null
     */
    public function pttl(string $key) {
        $this->_gc();
        $this->_sql->select('*', $this->_table)->where([
            'tag' => $this->_index . '_' . $this->_pre . $key,
            ['time_exp', '>', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            if ($obj = $ps->fetchObject()) {
                if ((int)$obj->time_exp === 4294967295) {
                    return -1;
                }
                $time = floor(microtime(true) * 1000);
                return ($obj->time_exp * 1000) - $time;
            }
            else {
                return null;
            }
        }
        catch (PDOException $e) {
            return null;
        }
    }

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array key => value 键值对
     */
    public function mGet(array $keys) {
        $this->_gc();
        $rtn = [];
        $prekeys = [];
        foreach ($keys as $v) {
            $prekeys[] = $this->_index . '_' . $this->_pre . $v;
            $rtn[$v] = null;
        }
        $this->_sql->select('*', $this->_table)->where([
            'tag' => $prekeys,
            ['time_exp', '>', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            $rows = [];
            while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
                $rows[$row['tag']] = $row['value'];
            }
            foreach ($prekeys as $k => $v) {
                if (!isset($rows[$v])) {
                    continue;
                }
                $rtn[$keys[$k]] = $rows[$v];
            }
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
        }
        return $rtn;
    }

    /**
     * --- 获取 json 对象 ---
     * @param string $key
     * @return mixed|null
     */
    public function getJson(string $key) {
        if (($v = $this->get($key)) === null) {
            return null;
        }
        $j = json_decode($v, true);
        return $j === null ? null : $j;
    }

    /**
     * --- 删除已存在的值 ---
     * @param string|string[] $key
     * @return bool
     */
    public function del($key) {
        $this->_gc();
        if (is_string($key)) {
            $key = [$this->_index . '_' . $this->_pre . $key];
        }
        else {
            foreach ($key as $k => $v) {
                $key[$k] = $this->_index . '_' . $this->_pre . $v;
            }
        }
        $this->_sql->delete($this->_table)->where([
            'tag' => $key,
            ['time_exp', '>', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            if ($ps->rowCount() > 0) {
                return true;
            }
            else {
                $this->_lastError = 'Key does not exist.';
                return false;
            }
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
    }


    /**
     * --- 自增 ---
     * @param string $key
     * @param int|float $num 整数或浮点正数
     * @return false|int|float
     */
    public function incr(string $key, $num = 1) {
        return $this->_incrDecr($key, $num, '+');
    }

    /**
     * --- 自减 ---
     * @param string $key
     * @param int|float $num 整数或浮点正数
     * @return false|int|float
     */
    public function decr(string $key, $num = 1) {
        return $this->_incrDecr($key, $num, '-');
    }

    /**
     * --- 内部使用自增自减 ---
     * @param string $key
     * @param int $value
     * @param string $op
     * @return false|int|float
     */
    private function _incrDecr(string $key, int $value, string $op = '+') {
        $this->_gc();

        $time = time();
        $this->_sql->insert($this->_table)->values([
            'tag' => $this->_index . '_' . $this->_pre . $key,
            'value' => $op === '+' ? $value : -$value,
            'time_add' => $time,
            'time_exp' => 4294967295
        ])->duplicate([
            'value' => ['#IF(`value` REGEXP ?, `value` ' . $op . ' ' . $value . ', `value`)', ['^-?\\d+\\.?\\d*$']]
        ]);

        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            if ($ps->rowCount() === 0) {
                $this->_lastError = 'ERR value is not an integer or out of range';
                return false;
            }
            $this->_sql->select(['value'], $this->_table)->where([
                'tag' => $this->_index . '_' . $this->_pre . $key
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            try {
                $ps->execute($this->_sql->getData());
                return (int)($ps->fetch(PDO::FETCH_ASSOC)['value']);
            }
            catch (PDOException $e) {
                $this->_lastError = $e->errorInfo[2];
                return false;
            }
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
    }

    /**
     * --- 仅修改过期时间不修改值 ---
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl) {
        $this->_gc();
        $time = time();
        $this->_sql->update($this->_table, [
            'time_exp' => $time + $ttl
        ])->where([
            'tag' => $this->_index . '_' . $this->_pre . $key,
            ['time_exp', '>', $time]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            return true;
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
    }

    /**
     * --- 获取服务器上的所有 key 列表（同步） ---
     * @param string $pattern
     * @return string[]|false
     */
    public function keys($pattern) {
        $this->_gc();
        $this->_sql->select(['tag'], $this->_table);
        $where = [
            ['time_exp', '>', time()]
        ];
        if ($pattern !== '*') {
            $pattern = str_replace('*', '%', $pattern);
            $where[] = ['tag', 'LIKE', $this->_index . '_' . $this->_pre . $pattern];
        }
        $this->_sql->where($where);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
        $rtn = [];
        $pl = strlen($this->_index . '_' . $this->_pre);
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            $rtn[] = substr($row['tag'], $pl);
        }
        return $rtn;
    }

    /**
     * --- 根据条件分批获取服务器上的 keys ---
     * @param int|null $cursor
     * @param string $pattern 例如 *
     * @param int $count 获取的条数
     * @return string[]|false
     */
    public function scan(&$cursor = null, $pattern = '*', $count = 10) {
        $this->_gc();
        if ($cursor === 0) {
            return false;
        }
        if ($cursor === null) {
            $cursor = 0;
        }
        $where = [
            ['time_exp', '>', time()],
            ['id', '>', $cursor]
        ];
        if ($pattern !== '*') {
            $pattern = str_replace('*', '%', $pattern);
            $where[] = ['tag', 'LIKE', $this->_index . '_' . $this->_pre . $pattern];
        }
        $this->_sql->where($where)->limit(0, $count);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
        $rtn = [];
        $pl = strlen($this->_index . '_' . $this->_pre);
        while ($row = $ps->fetch(PDO::FETCH_ASSOC)) {
            $rtn[] = substr($row['tag'], $pl);
        }
        return $rtn;
    }

    /**
     * --- 清除当前所选数据库的所有内容 ---
     * @return bool
     */
    public function flushDB() {
        $this->_sql->delete($this->_table)->where([
            ['tag', 'LIKE', $this->_index . '_%']
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
            return true;
        }
        catch (PDOException $e) {
            $this->_lastError = $e->errorInfo[2];
            return false;
        }
    }

    /**
     * --- 获取最后一次错误信息 ---
     * @return string|null
     */
    public function getLastError() {
        return $this->_lastError;
    }

    /**
     * --- 发送 ping ---
     * @return false|string
     */
    public function ping() {
        if (!$this->_link) {
            return false;
        }
        return $this->_link->isConnected() ? 'PONG' : false;
    }

    /**
     * --- 设置哈希表值 ---
     * @param string $key key 名
     * @param string $field 字段名
     * @param mixed $val 值
     * @param string $mod 空,nx(key不存在才建立)
     * @return bool
     */
    public function hSet(string $key, string $field, $val, string $mod = '') {
        if (is_array($val)) {
            $val = json_encode($val);
        }
        if ($mod === 'nx') {
            $r = $this->set($key . '-' . $field, $val, 0, 'nx');
        }
        else {
            $r = $this->set($key . '-' . $field, $val);
        }
        if ($r === false) {
            return false;
        }
        if (($v = $this->get($key)) === null) {
            $this->set($key, '-' . $field . '-');
        }
        else {
            if (strpos($v, '-' . $field . '-') === false) {
                $this->append($key, $field . '-');
            }
        }
        return true;
    }

    /**
     * --- 批量设置哈希值 ---
     * @param string $key key my
     * @param array $rows key / val 数组
     * @return bool
     */
    public function hMSet(string $key, array $rows) {
        foreach ($rows as $k => $v) {
            if (!$this->hSet($key, $k, $v)) {
                return false;
            }
        }
        return true;
    }

    /**
     * --- 获取哈希值 ---
     * @param string $key
     * @param string $field
     * @return string|null
     */
    public function hGet(string $key, string $field) {
        return $this->get($key . '-' . $field);
    }

    /**
     * --- 获取哈希 json 对象 ---
     * @param string $key
     * @param string $field
     * @return mixed|null
     */
    public function hGetJson(string $key, string $field) {
        if (($v = $this->hGet($key, $field)) === null) {
            return null;
        }
        $j = json_decode($v, true);
        return $j === null ? null : $j;
    }


    /**
     * --- 批量获取哈希值 ---
     * @param string $key
     * @param array $fields
     * @return array
     */
    public function hMGet(string $key, array $fields) {
        $inKeys = [];
        foreach ($fields as $v) {
            $inKeys[] = $key . '-' . $v;
        }
        $kl = strlen($key) + 1;
        $r = $this->mGet($inKeys);
        $rtn = [];
        foreach ($r as $k => $v) {
            $rtn[substr($k, $kl)] = $v;
        }
        return $rtn;
    }

    /**
     * --- 批量获取哈希键值对 ---
     * @param string $key
     * @return array
     */
    public function hGetAll(string $key) {
        if (($v = $this->get($key)) === null) {
            return [];
        }
        if ($v === '-') {
            return [];
        }
        $r = [];
        $v = substr($v, 1, -1);
        $keys = explode('-', $v);
        foreach ($keys as $k) {
            $r[$k] = $this->get($key . '-' . $k);
        }
        return $r;
    }

    /**
     * --- 删除哈希键 ---
     * @param string $key key
     * @param string|string[] $fields 值序列
     * @return int
     */
    public function hDel(string $key, $fields) {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $count = 0;
        foreach ($fields as $field) {
            if ($this->del($key . '-' . $field)) {
                ++$count;
            }
        }
        if ($v = $this->get($key)) {
            foreach ($fields as $field) {
                $v = str_replace('-' . $field . '-', '-', $v);
            }
            if ($v === '-') {
                $this->del($v);
            }
            else {
                $this->set($key, $v);
            }
        }
        return $count;
    }

    /**
     * --- 判断哈希字段是否存在 ---
     * @param string $key
     * @param string $field
     * @return bool
     */
    public function hExists(string $key, string $field) {
        return $this->exists($key . '-' . $field) === 1 ? true : false;
    }

    /**
     * --- 设置哈希自增自减 ---
     * @param string $key
     * @param string $field
     * @param $increment
     * @return float|int
     */
    public function hIncr(string $key, string $field, $increment) {
        if ($increment >= 0) {
            return $this->incr($key . '-' . $field, $increment);
        }
        else {
            return $this->decr($key . '-' . $field, abs($increment));
        }
    }

    /**
     * --- 获取哈希所有字段 ---
     * @param string $key
     * @return array
     */
    public function hKeys(string $key) {
        if ($v = $this->get($key)) {
            $v = substr($v, 1, -1);
            return explode('-', $v);
        }
        else {
            return [];
        }
    }

    /**
     * --- 根据 5% 概率在数据库里删除过期数据 ---
     */
    private function _gc(): void {
        if(rand(0, 19) == 10) {
            $this->_gcDo();
        }
    }

    /**
     * --- 强制删除数据库里的过期数据 ---
     */
    private function _gcDo(): void {
        $this->_sql->delete($this->_table)->where([
            ['time_exp', '<', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            // --- 啥也不处理 ---
        }
    }

}

