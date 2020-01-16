<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2017/01/31 10:30
 * Last: 2018-12-12 12:29:14, 2019-12-18 17:17:49
 */
declare(strict_types = 1);

namespace lib\Kv;

use RedisException;

class Redis implements IKv {

    /* @var \Redis $_link */
    private $_link = null;

    /** @var string key 的前置 */
    private $_pre = '';

    /** @var array 当前连接的 redis 服务器信息（兼容 Memcached） */
    private $_serverList = [];
    /** @var int 最后一次执行返回的 code（兼容 Memcached） */
    private $_resultCode = 0;
    /** @var string 最后一次执行返回的说明（兼容 Memcached） */
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
        if (!$this->_link->connect($host, $port, 1.0)) {
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
        if(is_array($val)) {
            $val = json_encode($val);
        }
        $opt = [];
        if($mod != '') {
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
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $r = $this->_link->append($this->_pre . $key, $val);
        if ($r === false) {
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
        $r = $this->_link->evalSha('ea360f3f6508a243824ecda6be15db56df217873', [$this->_pre.$key, $val], 1);
        if ($r === false) {
            $this->_link->script('load', $script);
            $r = $this->_link->evalSha('ea360f3f6508a243824ecda6be15db56df217873', [$this->_pre.$key, $val], 1);
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
     * --- 获取数值和字符串 ---
     * @param string $key
     * @return mixed|false
     */
    public function get(string $key) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if(($v = $this->_link->get($this->_pre . $key)) === false) {
            $this->_resultCode = -1;
            $this->_resultMessage = $this->_link->getLastError();
            return false;
        }
        return $v;
    }

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array
     */
    public function mget(array $keys) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        foreach ($keys as $k => $v) {
            $keys[$k] = $this->_pre . $v;
        }
        return $this->_link->mget($keys);
    }

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array
     */
    public function getMulti(array $keys) {
        return $this->mget($keys);
    }

    /**
     * --- 获取 json 对象 ---
     * @param string $key
     * @return bool|mixed
     */
    public function getJson(string $key) {
        if (($v = $this->get($key)) === false) {
            return false;
        }
        $j = json_decode($v);
        return $j === null ? false : $j;
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
     * @param int $num
     * @return false|int
     */
    public function incr(string $key, int $num = 1) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if ($num === 1) {
            $r = $this->_link->incr($this->_pre . $key);
        } else {
            $r = $this->_link->incrBy($this->_pre . $key, $num);
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
     * @param int $num
     * @return false|int
     */
    public function decr(string $key, int $num = 1) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        if ($num === 1) {
            $r = $this->_link->decr($this->_pre . $key);
        } else {
            $r = $this->_link->decrBy($this->_pre . $key, $num);
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
     * --- 获取服务器上的所有 key 列表（Memcached 下有延迟且 binary 需为 false） ---
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
        }
        $pl = strlen($this->_pre);
        if ($pl > 0) {
            foreach ($r as $k => $v) {
                $r[$k] = substr($v, $pl);
            }
        }
        return $r;
    }

    /**
     * --- 根据条件获取服务器上的 key ---
     * @param string $pattern
     * @param int $count Count of keys per iteration (only a suggestion to Redis).
     * @return string[]|false
     */
    public function scan($pattern = '*', $count = 0) {
        $this->_resultCode = 0;
        $this->_resultMessage = 'SUCCESS';
        $pl = strlen($this->_pre);
        $keys = [];
        $iterator = null;
        while (($r = $this->_link->scan($iterator, $this->_pre . $pattern, $count)) !== false) {
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
     * --- 清除服务器上所有的数据 ---
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

}

