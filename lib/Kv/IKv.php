<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-12-21 11:56:27
 * Last: 2019-12-21 11:56:30, 2020-3-29 21:29:53
 */
declare(strict_types = 1);

namespace lib\Kv;

interface IKv {

    /**
     * @param array $opt
     * @return bool|null
     */
    public function connect(array $opt = []);

    /**
     * --- 判断是否连接成功 ---
     * @return bool
     */
    public function isConnect();

    /**
     * --- 退出断开连接 ---
     */
    public function quit(): void;

    /**
     * --- 设定一个值 ---
     * @param string $key
     * @param mixed $val
     * @param int $ttl 秒，0 为不限制
     * @param string $mod 设置模式: 空,nx（key不存在才建立）,xx（key存在才修改）
     * @return bool
     */
    public function set(string $key, $val, int $ttl = 0, string $mod = '');

    /**
     * --- 添加一个值，存在则不变 ---
     * @param string $key
     * @param $val
     * @param int $ttl 有效期
     * @return bool
     */
    public function add(string $key, $val, int $ttl = 0): bool;

    /**
     * --- 替换一个存在的值 ---
     * @param string $key
     * @param $val
     * @param int $ttl
     * @return bool
     */
    public function replace(string $key, $val, int $ttl = 0);

    /**
     * --- 向已存在的值后追加数据 ---
     * @param string $key
     * @param $val
     * @return bool
     */
    public function append(string $key, $val);

    /**
     * --- 向已存在的值之前追加数据 ---
     * @param string $key
     * @param $val
     * @return bool
     */
    public function prepend(string $key, $val);

    /**
     * --- 检测 key 是否存在 ---
     * @param string[]|string $key 单个或序列
     * @return int
     */
    public function exists($key);

    /**
     * --- 获取数值和字符串 ---
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key);

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array 顺序数组
     */
    public function mGet(array $keys);

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array key => value 键值对
     */
    public function getMulti(array $keys);

    /**
     * --- 获取 json 对象 ---
     * @param string $key
     * @return bool|null
     */
    public function getJson(string $key);

    /**
     * --- 删除已存在的值 ---
     * @param string|string[] $key
     * @return bool
     */
    public function delete($key);

    /**
     * --- 自增 ---
     * @param string $key
     * @param int $num
     * @return false|int
     */
    public function incr(string $key, int $num = 1);

    /**
     * --- 自减 ---
     * @param string $key
     * @param int $num
     * @return false|int
     */
    public function decr(string $key, int $num = 1);

    /**
     * --- 仅修改过期时间不修改值 ---
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function touch(string $key, int $ttl);

    /**
     * --- 仅修改过期时间不修改值 ---
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl);

    /**
     * --- 获取服务器上的所有 key 列表（Memcached 下有延迟且 binary 需为 false） ---
     * @return string[]|false
     */
    public function getAllKeys();

    /**
     * --- 获取服务器上的所有 key 列表（同步） ---
     * @param string $pattern
     * @return string[]|false
     */
    public function keys($pattern);

    /**
     * --- 根据条件获取服务器上的 key ---
     * @param string $pattern
     * @return string[]|false
     */
    public function scan($pattern = '*');

    /**
     * --- 清除服务器上所有的数据 ---
     * @return bool
     */
    public function flush();

    /**
     * --- 清除服务器上所有的数据 ---
     * @return bool
     */
    public function flushDB();

    /**
     * --- 获取最后一次执行结果码 ---
     * @return int
     */
    public function getResultCode();

    /**
     * --- 获取最后一次执行结果文本 ---
     * @return string
     */
    public function getResultMessage();

    /**
     * --- 获取最后一次错误信息 ---
     * @return string|null
     */
    public function getLastError();

    /**
     * --- 获取当前服务器列表 ---
     * @return array
     */
    public function getServerList();

    /**
     * --- 清除所有已连接的 server ---
     * @return bool
     */
    public function resetServerList();

    /**
     * --- 发送 ping ---
     * @return false|string
     */
    public function ping();

    /**
     * --- 获取状态 ---
     * @param string $name
     * @return array
     */
    public function getStats(string $name);

    /**
     * --- 设置哈希表值 ---
     * @param string $key key 名
     * @param string $field 字段名
     * @param mixed $val 值
     * @param string $mod 空,nx(key不存在才建立)
     * @return bool
     */
    public function hSet(string $key, string $field, $val, string $mod = '');

    /**
     * --- 批量设置哈希值 ---
     * @param string $key key my
     * @param array $rows key / val 数组
     * @return bool
     */
    public function hMSet(string $key, array $rows);

    /**
     * --- 获取哈希值 ---
     * @param string $key
     * @param string $field
     * @return string|null
     */
    public function hGet(string $key, string $field);

    /**
     * --- 获取哈希 json 对象 ---
     * @param string $key
     * @param string $field
     * @return mixed|null
     */
    public function hGetJson(string $key, string $field);

    /**
     * --- 批量获取哈希值 ---
     * @param string $key
     * @param array $fields
     * @return array
     */
    public function hMGet(string $key, array $fields);

    /**
     * --- 批量获取哈希键值对 ---
     * @param string $key
     * @return array
     */
    public function hGetAll(string $key);

    /**
     * --- 删除哈希键 ---
     * @param string $key key
     * @param string|string[] $fields 值序列
     * @return int
     */
    public function hDel(string $key, $fields);

    /**
     * --- 判断哈希字段是否存在 ---
     * @param string $key
     * @param string $field
     * @return bool
     */
    public function hExists(string $key, string $field);

    /**
     * --- 设置哈希自增自减 ---
     * @param string $key
     * @param string $field
     * @param $increment
     * @return float|int
     */
    public function hIncr(string $key, string $field, $increment);

    /**
     * --- 获取哈希所有字段 ---
     * @param string $key
     * @return array
     */
    public function hKeys(string $key);

}

