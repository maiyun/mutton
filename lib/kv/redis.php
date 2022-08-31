<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2017/01/31 10:30
 * Last: 2018-12-12 12:29:14, 2019-12-18 17:17:49, 2020-3-28 13:21:02, 2020-4-5 22:05:08, 2022-3-24 22:46:41, 2022-08-31 15:20:41
 */
declare(strict_types = 1);

namespace lib\Kv;

use RedisException;

class Redis implements IKv {

    /* @var \Redis $_link */
    private $_link = null;

    /** @var string key 的前置 */
    private $_pre = '';

    /** @var array 当前连接的 redis 服务器信息 */
    private $_serverList = [];
    /** @var int 最后一次执行返回的 code */
    private $_resultCode = 0;
    /** @var string 最后一次执行返回的说明 */
    private $_resultMessage = 'SUCCESS';

    /**
     * @param array $opt
     * @return bool|null
     */
    public function connect(array $opt = []) {
        $host =  isset($opt['host']) ? $opt['host'] : RD_HOST;
        $port = isset($opt['port']) ? $opt['port'] : RD_PORT;
        $user = isset($opt['user']) ? $opt['user'] : RD_USER;
        $pwd = isset($opt['pwd']) ? $opt['pwd'] : RD_PWD;
        $index = isset($opt['index']) ? $opt['index'] : RD_INDEX;
        $this->_pre = isset($opt['pre']) ? $opt['pre'] : RD_PRE;

        if (!class_exists('\\Redis')) {
            return null;
        }

        $this->_link = new \Redis();
        try {
            $this->_link->connect($host, $port, 1.0);
        }
        catch (RedisException $e) {
            return false;
        }
        if (($user !== '' || $pwd !== '') && !$this->_link->auth($user . ':' . $pwd)) {
            return false;
        }
        $this->_link->select($index);

        $this->_serverList[] = [
            'host' => $host,
            'port' => $port,
            'type' => 'TCP'
        ];
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
        try {
            $r = $this->_link->ping();
            return $r === true || $r === '+PONG' ? true : false;
        } catch (RedisException $e) {
            return false;
        }
    }

    /**
     * --- 退出断开连接 ---
     */
    public function quit(): void {
        // --- 无需退出，会复用 ---
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
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_array($val)) {
            $val = json_encode($val);
        }
        $opt = [];
        if ($mod != '') {
            $opt[] = $mod;
        }
        if ($ttl > 0) {
            $opt['ex'] = $ttl;
        }
        $r = $this->_link->set($this->_pre . $key, $val, $opt);
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r;
    }

    /**
     * --- 添加一个值，存在则不变 ---
     * @param string $key
     * @param $val
     * @param int $ttl 秒，0 为不限制
     * @return bool
     */
    public function add(string $key, $val, int $ttl = 0): bool {
        return $this->set($key, $val, $ttl, 'nx');
    }

    /**
     * --- 替换一个存在的值 ---
     * @param string $key
     * @param $val
     * @param int $ttl 秒，0 为不限制
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
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $r = $this->_link->append($this->_pre . $key, $val);
        if ($r <= 0) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r > 0 ? true : false;
    }

    /**
     * --- 向已存在的值之前追加数据 ---
     * @param string $key
     * @param $val
     * @return bool
     */
    public function prepend(string $key, $val) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $script = <<<SCRIPT
local val = redis.call("GET", KEYS[1])
if (val == false) then
    return 0
end
local r = redis.call("SET", KEYS[1], ARGV[1]..val)
if (r) then
    return 1
else
    return 0
end
SCRIPT;
        $r = $this->_link->evalSha('ea360f3f6508a243824ecda6be15db56df217873', [$this->_pre . $key, $val], 1);
        if ($r === false) {
            $this->_link->script('load', $script);
            $r = $this->_link->evalSha('ea360f3f6508a243824ecda6be15db56df217873', [$this->_pre . $key, $val], 1);
            if ($r <= 0) {
                $this->_resultCode = -1;
                $this->_resultMessage = $this->_link->getLastError();
            }
        }
        return $r > 0 ? true : false;
    }

    /**
     * --- 检测 key 是否存在 ---
     * @param string[]|string $key 单个或序列
     * @return int
     */
    public function exists($key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_string($key)) {
            $key = $this->_pre . $key;
        } else {
            foreach ($key as $k => $v) {
                $key[$k] = $this->_pre . $v;
            }
        }
        $r = $this->_link->exists($key);
        if (is_bool($r)) {
            return $r ? 1 : 0;
        } else {
            return $r;
        }
    }

    /**
     * --- 获取字符串 ---
     * @param string $key
     * @return string|null
     */
    public function get(string $key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if(($v = $this->_link->get($this->_pre . $key)) === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
            return null;
        }
        return $v;
    }

    /**
     * --- 获取相应的剩余有效期秒数 ---
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $r = $this->_link->ttl($this->_pre . $key);
        if ($r === -2) {
            return null;
        }
        return $r;
    }

    /**
     * --- 获取相应的剩余有效期毫秒数 ---
     * @param string $key
     * @return int|null
     */
    public function pttl(string $key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $r = $this->_link->pttl($this->_pre . $key);
        if ($r === -2) {
            return null;
        }
        return $r;
    }

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array 顺序数组
     */
    public function mGet(array $keys) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        foreach ($keys as $k => $v) {
            $keys[$k] = $this->_pre . $v;
        }
        $rtn = $this->_link->mget($keys);
        foreach ($rtn as $k => $v) {
            if ($v === false) {
                $rtn[$k] = null;
            }
        }
        return $rtn;
    }

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array key => value 键值对
     */
    public function getMulti(array $keys) {
        $r = $this->mGet($keys);
        $rtn = [];
        foreach ($keys as $k => $v) {
            $rtn[$v] = $r[$k];
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
    public function delete($key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_string($key)) {
            $r = $this->_link->del($this->_pre . $key) > 0 ? true : false;
        } else {
            foreach ($key as $k => $v) {
                $key[$k] = $this->_pre . $v;
            }
            $r = $this->_link->del($key) > 0 ? true : false;
        }
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r;
    }

    /**
     * --- 自增 ---
     * @param string $key
     * @param int|float $num 整数或浮点正数
     * @return false|int|float
     */
    public function incr(string $key, int $num = 1) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_int($num)) {
            if ($num === 1) {
                $r = $this->_link->incr($this->_pre . $key);
            } else {
                $r = $this->_link->incrBy($this->_pre . $key, $num);
            }
        } else {
            $r = $this->_link->incrByFloat($this->_pre . $key, $num);
        }
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r;
    }

    /**
     * --- 自减 ---
     * @param string $key
     * @param int|float $num 整数或浮点正数
     * @return false|int|float
     */
    public function decr(string $key, int $num = 1) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_int($num)) {
            if ($num === 1) {
                $r = $this->_link->decr($this->_pre . $key);
            } else {
                $r = $this->_link->decrBy($this->_pre . $key, $num);
            }
        } else {
            $r = $this->_link->incrByFloat($this->_pre . $key, -$num);
        }
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r;
    }

    /**
     * --- 仅修改过期时间不修改值 ---
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function touch(string $key, int $ttl) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $r = $this->_link->expire($this->_pre . $key, $ttl);
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r;
    }

    /**
     * --- 仅修改过期时间不修改值 ---
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl) {
        return $this->touch($key, $ttl);
    }

    /**
     * --- 获取服务器上的所有 key 列表 ---
     * @return string[]|false
     */
    public function getAllKeys() {
        return $this->scan();
    }

    /**
     * --- 获取服务器上的所有 key 列表（同步） ---
     * @param string $pattern
     * @return string[]|false
     */
    public function keys($pattern) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $r = $this->_link->keys($this->_pre . $pattern);
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        } else {
            $pl = strlen($this->_pre);
            if ($pl > 0) {
                foreach ($r as $k => $v) {
                    $r[$k] = substr($v, $pl);
                }
            }
        }
        return $r;
    }

    /**
     * --- 根据条件获取服务器上的 keys ---
     * @param string $pattern 例如 *
     * @return string[]|false
     */
    public function scan($pattern = '*') {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $pl = strlen($this->_pre);
        $keys = [];
        $iterator = null;
        while (($r = $this->_link->scan($iterator, $this->_pre . $pattern)) !== false) {
            foreach ($r as $k => $v) {
                $keys[] = $pl > 0 ? substr($v, $pl) : $v;
            }
        }
        if ($this->_link->getLastError()) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
            return false;
        }
        return array_unique($keys);
    }

    /**
     * --- 清除当前所选数据库的所有内容 ---
     * @return bool
     */
    public function flush() {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        return $this->_link->flushDB();
    }

    /**
     * --- 清除服务器上所有的数据 ---
     * @return bool
     */
    public function flushDB() {
        return $this->flush();
    }

    /**
     * --- 获取最后一次执行结果码 ---
     * @return int
     */
    public function getResultCode() {
        return $this->_resultCode;
    }

    /**
     * --- 获取最后一次执行结果文本 ---
     * @return string
     */
    public function getResultMessage() {
        return $this->_resultMessage;
    }

    /**
     * --- 获取最后一次错误信息 ---
     * @return string|null
     */
    public function getLastError() {
        return $this->_link->getLastError();
    }

    /**
     * --- 获取当前服务器列表 ---
     * @return array
     */
    public function getServerList() {
        if ($this->_link === null || !$this->isConnect()) {
            return [];
        }
        return $this->_serverList;
    }

    /**
     * --- 清除所有已连接的 server ---
     * @return bool
     */
    public function resetServerList() {
        $this->_link = new \Redis();
        $this->_serverList = [];
        return true;
    }

    /**
     * --- 发送 ping ---
     * @return false|string
     */
    public function ping() {
        try {
            $r = $this->_link->ping();
            return $r === true || $r === '+PONG' ? '+PONG' : false;
        } catch (RedisException $e) {
            return false;
        }
    }

    /**
     * --- 获取状态 ---
     * @param string $name
     * @return array
     */
    public function getStats(string $name) {
        return [];
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
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_array($val)) {
            $val = json_encode($val);
        }
        if ($mod === 'nx') {
            $r = $this->_link->hSetNx($this->_pre . $key, $field, $val);
        } else {
            $r = $this->_link->hSet($this->_pre . $key, $field, $val);
        }
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r === false ? false : true;
    }

    /**
     * --- 批量设置哈希值 ---
     * @param string $key key 名
     * @param array $rows key / val 数组
     * @return bool
     */
    public function hMSet(string $key, array $rows) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        foreach ($rows as $i => $val) {
            if (is_array($val)) {
                $rows[$i] = json_encode($val);
            }
        }
        $r = $this->_link->hMSet($this->_pre . $key, $rows);
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
        }
        return $r;
    }

    /**
     * --- 获取哈希值 ---
     * @param string $key
     * @param string $field
     * @return string|null
     */
    public function hGet(string $key, string $field) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $r = $this->_link->hGet($this->_pre . $key, $field);
        if ($r === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
            return null;
        }
        return $r;
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
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        return $this->_link->hMGet($this->_pre . $key, $fields);
    }

    /**
     * --- 批量获取哈希键值对 ---
     * @param string $key
     * @return array
     */
    public function hGetAll(string $key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        return $this->_link->hGetAll($this->_pre . $key);
    }

    /**
     * --- 删除哈希键 ---
     * @param string $key key
     * @param string|string[] $fields 值序列
     * @return int
     */
    public function hDel(string $key, $fields) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_string($fields)) {
            $fields = [$fields];
        }
        $r = $this->_link->hDel($this->_pre . $key, ...$fields);
        if (is_bool($r)) {
            if ($r === false) {
                $this->_resultCode = -1;
                $this->_resultMessage = $this->_link->getLastError();
            }
            return $r ? 1 : 0;
        } else {
            return $r;
        }
    }

    /**
     * --- 判断哈希字段是否存在 ---
     * @param string $key
     * @param string $field
     * @return bool
     */
    public function hExists(string $key, string $field) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        return $this->_link->hExists($this->_pre . $key, $field);
    }

    /**
     * --- 设置哈希自增自减 ---
     * @param string $key key
     * @param string $field 字段
     * @param float|int $increment 正数或负数，整数或浮点
     * @return float|int
     */
    public function hIncr(string $key, string $field, $increment) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if (is_int($increment)) {
            $r = $this->_link->hIncrBy($this->_pre . $key, $field, $increment);
        } else {
            $r = $this->_link->hIncrByFloat($this->_pre . $key, $field, $increment);
        }
        return $r;
    }

    /**
     * --- 获取哈希所有字段 ---
     * @param string $key
     * @return array
     */
    public function hKeys(string $key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        return $this->_link->hKeys($this->_pre . $key);
    }

}

