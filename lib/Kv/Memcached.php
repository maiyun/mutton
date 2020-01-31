<?php
/**
 * User: JianSuoQiYue
 * Date: 2017/07/04 22:48
 * Last: 2018-12-12 17:48:43, 2019-12-16 12:53:35
 */
declare(strict_types = 1);

namespace lib\Kv;

class Memcached implements IKv {

    /* @var $_link \Memcached */
    private $_link = null;

    /** @var string key 的前置 */
    private $_pre = '';

    /** @var string|null 最后一次错误信息（兼容 Redis） */
    private $_lastError = null;

    /**
     * @param array $opt
     * @return bool|null
     */
    public function connect(array $opt = []) {
        $host = isset($opt['host']) ? $opt['host'] : MC_HOST;
        $port = isset($opt['port']) ? $opt['port'] : MC_PORT;
        $user = isset($opt['user']) ? $opt['user'] : MC_USER;
        $pwd = isset($opt['pwd']) ? $opt['pwd'] : MC_PWD;
        $pool = isset($opt['pool']) ? $opt['pool'] : MC_POOL;
        $binary = isset($opt['binary']) ? $opt['binary'] : MC_BINARY;
        $this->_pre = isset($opt['pre']) ? $opt['pre'] : MC_PRE;

        if (!class_exists('\\Memcached')) {
            return null;
        }
        if ($pool != '') {
            $this->_link = new \Memcached();
        } else {
            $this->_link = new \Memcached($pool);
            if ($this->isConnect()) {
                return true;
            }
        }

        $this->_link->setOption(\Memcached::OPT_COMPRESSION, false);
        $this->_link->setOption(\Memcached::OPT_BINARY_PROTOCOL, $binary);
        $this->_link->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 1000); // 毫秒

        if (!$this->_link->addServer($host, $port)) {
            return false;
        }
        if (($user != '') || ($pwd != '')) {
            $this->_link->setSaslAuthData($user, $pwd);
        }
        return true;
    }

    /**
     * --- 判断是否连接成功 ---
     * @return bool
     */
    public function isConnect() {
        if (!count($this->getServerList())) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * --- 退出断开连接 ---
     */
    public function quit(): void {
        $this->_link->quit();
        $this->_link = null;
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
        if(is_array($val)) {
            $val = json_encode($val);
        }
        if ($mod == '') {
            $r = $this->_link->set($this->_pre . $key, $val, $ttl);
        } else if ($mod == 'nx') {
            $r = $this->_link->add($this->_pre . $key, $val, $ttl);
        } else {
            $r = $this->_link->replace($this->_pre . $key, $val, $ttl);
        }
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
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
        $r = $this->_link->append($this->_pre . $key, $val);
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
        }
        return $r;
    }

    /**
     * --- 向已存在的值之前追加数据 ---
     * @param string $key
     * @param $val
     * @return bool
     */
    public function prepend(string $key, $val) {
        $r = $this->_link->prepend($this->_pre . $key, $val);
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
        }
        return $r;
    }

    /**
     * --- 检测 key 是否存在 ---
     * @param string[]|string $key 单个或序列
     * @return int
     */
    public function exists($key) {
        $rtn = 0;
        if (is_string($key)) {
            $key = [$key];
        }
        foreach ($key as $v) {
            if ($this->_link->append($this->_pre . $v, '')) {
                ++$rtn;
            }
        }
        return $rtn;
    }

    /**
     * --- 获取数值和字符串 ---
     * @param string $key
     * @return mixed|false
     */
    public function get(string $key) {
        $v = $this->_link->get($this->_pre . $key);
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
        }
        if($v === false) {
            return false;
        }
        return $v;
    }

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array 顺序数组
     */
    public function mget(array $keys) {
        $rtn = [];
        $list = $this->getMulti($keys);
        foreach ($keys as $v) {
            $rtn[] = $list[$v];
        }
        return $rtn;
    }

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array key => value 键值对
     */
    public function getMulti(array $keys) {
        $inKeys = [];
        foreach ($keys as $k => $v) {
            $inKeys[$k] = $this->_pre . $v;
        }
        $r = $this->_link->getMulti($inKeys, \Memcached::GET_PRESERVE_ORDER);
        if ($r === false) {
            $rtn = [];
            foreach ($keys as $v) {
                $rtn[$v] = null;
            }
            return $rtn;
        }
        $rtn = [];
        foreach ($keys as $v) {
            if ($r[$this->_pre . $v] === null) {
                $rtn[$v] = null;
            } else {
                $rtn[$v] = $r[$this->_pre . $v];
            }
        }
        return $rtn;
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
        if (is_string($key)) {
            $r = $this->_link->delete($this->_pre . $key);
        } else {
            foreach ($key as $k => $v) {
                $key[$k] = $this->_pre . $v;
            }
            $r = $this->_link->deleteMulti($key);
            if (count($r) !== count($key)) {
                return false;
            }
            $r = true;
        }
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
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
        $r = $this->_link->increment($this->_pre . $key, $num);
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
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
        $r = $this->_link->decrement($this->_pre . $key, $num);
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
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
        $r = $this->_link->touch($this->_pre . $key, $ttl);
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
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
        return $this->_link->getAllKeys();
    }

    /**
     * --- 获取服务器上的所有 key 列表（同步） ---
     * @param string $pattern
     * @return string[]|false
     */
    public function keys($pattern) {
        $keys = $this->getAllKeys();
        if ($keys === false) {
            return false;
        }
        $rtn = [];
        $p = str_replace('*', '[\s\S]*', $pattern);
        foreach ($keys as $v) {
            if (preg_match('/^' . $p . '$/', $v)) {
                $rtn[] = $v;
            }
        }
        return $rtn;
    }

    /**
     * --- 根据条件获取服务器上的 key ---
     * @param string $pattern
     * @return string[]|false
     */
    public function scan($pattern = '*') {
        return $this->keys($pattern);
    }

    /**
     * --- 清除服务器上所有的数据 ---
     * @return bool
     */
    public function flush() {
        $r = $this->_link->flush();
        if (($msg = $this->_link->getResultMessage()) !== 'SUCCESS') {
            $this->_lastError = $msg;
        }
        return $r;
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
        return $this->_link->getResultCode();
    }

    /**
     * --- 获取最后一次执行结果文本 ---
     * @return string
     */
    public function getResultMessage() {
        return $this->_link->getResultMessage();
    }

    /**
     * --- 获取最后一次错误信息 ---
     * @return string|null
     */
    public function getLastError() {
        return $this->_lastError;
    }

    /**
     * --- 获取当前服务器列表 ---
     * @return array
     */
    public function getServerList() {
        if ($this->_link === null) {
            return [];
        }
        return $this->_link->getServerList();
    }

    /**
     * --- 清除所有已连接的 server ---
     * @return bool
     */
    public function resetServerList() {
        if ($this->_link === null) {
            return false;
        }
        return $this->_link->resetServerList();
    }

    /**
     * --- 发送 ping ---
     * @return false|string
     */
    public function ping() {
        if ($this->isConnect()) {
            return '+PONG';
        } else {
            return false;
        }
    }

    /**
     * --- 获取状态 ---
     * @param string $name
     * @return array
     */
    public function getStats(string $name) {
        return $this->_link->getStats($name);
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
        } else {
            $r = $this->set($key . '-' . $field, $val);
        }
        if ($r === false) {
            return false;
        }
        if (($v = $this->get($key)) === false) {
            $this->set($key, '-' . $field . '-');
        } else {
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
     * @return string|false
     */
    public function hGet(string $key, string $field) {
        return $this->get($key . '-' . $field);
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
        $r = $this->getMulti($inKeys);
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
        if (($v = $this->get($key)) === false) {
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
            if ($this->delete($key . '-' . $field)) {
                ++$count;
            }
        }
        if ($v = $this->get($key)) {
            foreach ($fields as $field) {
                $v = str_replace('-' . $field . '-', '-', $v);
            }
            if ($v === '-') {
                $this->delete($v);
            } else {
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
        } else {
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
        } else {
            return [];
        }
    }

}

