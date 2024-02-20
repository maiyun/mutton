<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-12-21 11:56:27
 * Last: 2019-12-21 11:56:30, 2020-3-29 21:29:53, 2022-08-31 15:20:29, 2024-2-20 11:50:09
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
     * --- 获取相应的剩余有效期秒数 ---
     * @param string $key
     * @return int|null
     */
    public function ttl(string $key);

    /**
     * --- 获取相应的剩余有效期毫秒数 ---
     * @param string $key
     * @return int|null
     */
    public function pttl(string $key);

    /**
     * --- 批量获取值 ---
     * @param array $keys key 序列
     * @return array 顺序数组
     */
    public function mGet(array $keys);

    /**
     * --- 获取 json 对象 ---
     * @param string $key
     * @return mixed|null
     */
    public function getJson(string $key);

    /**
     * --- 删除已存在的值 ---
     * @param string|string[] $key
     * @return bool
     */
    public function del($key);

    /**
     * --- 自增 ---
     * @param string $key
     * @param int|float $num 整数或浮点正数
     * @return false|int
     */
    public function incr(string $key, $num = 1);

    /**
     * --- 自减 ---
     * @param string $key
     * @param int|float $num 整数或浮点正数
     * @return false|int
     */
    public function decr(string $key, $num = 1);

    /**
     * --- 仅修改过期时间不修改值 ---
     * @param string $key
     * @param int $ttl
     * @return bool
     */
    public function expire(string $key, int $ttl);

    /**
     * --- 获取服务器上的所有 key 列表（同步） ---
     * @param string $pattern
     * @return string[]|false
     */
    public function keys($pattern);

    /**
     * --- 根据条件分批获取服务器上的 keys ---
     * @param int|null $cursor
     * @param string $pattern 例如 *
     * @param int $count 获取的条数
     * @return string[]|false
     */
    public function scan(&$cursor = null, $pattern = '*', $count = 10);

    /**
     * --- 清除当前所选数据库的所有内容 ---
     * @return bool
     */
    public function flushDB();

    /**
     * --- 获取最后一次错误信息 ---
     * @return string|null
     */
    public function getLastError();

    /**
     * --- 发送 ping ---
     * @return false|string
     */
    public function ping();

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

    public function lPush(string $key, array $values): int;

    public function rPush(string $key, array $values): int;

    public function bLMove(string $src, string $dst, string $wherefrom, string $whereto, float $timeout): string | null;

    public function lPop(string $key): string;

    public function rPop(string $key): string;
    
    public function bRPop(string | array $key, string | float | int $timeout): array;

    public function lRange(string $key, int $start, int $end): array;

    public function lLen(string $key): int;

}

