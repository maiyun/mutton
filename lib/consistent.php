<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2022-09-01 21:56:06
 * Last: 2022-09-01 21:56:06, 2022-09-03 00:56:29, 2023-11-15 11:45:34
 */
declare(strict_types = 1);

namespace lib;

class Consistent {

    /** --- 虚拟节点数量 --- */
    private $_vcount = 300;

    /** --- hash 环 --- */
    private $_circle = [];

    /** --- circle 的 keys --- */
    private $_keys = [];

    public function __construct($vcount) {
        $this->_vcount = $vcount;
    }
    
    /**
    * @return Consistent
    */
    public static function get($vcount = 300): Consistent {
        return new Consistent($vcount);
    }

    /**
     * --- 快速查找一个 key 属于哪个 node ---
     * @param string|int|float $key 要查找的key
     * @param array $nodes node 列表
     * @param int $vcount 虚拟节点数量
     */
    public static function fast(string|int|float $key, array $nodes, int $vcount = 300) {
        $circle = [];
        self::addToCircle($circle, $nodes, $vcount);
        return self::findInCircle($circle, $key);
    }

    /**
     * --- hash 函数 ---
     * @param string|int|float $val 要 hash 的值
     */
    public static function hash($val) {
        if (is_int($val) || is_float($val)) {
            $val = (string)$val;
        }
        $bKey = md5($val);
        $res = ((ord($bKey[3]) & 0xFF) << 24) | 
            ((ord($bKey[2]) & 0xFF) << 16) | 
            ((ord($bKey[1]) & 0xFF) << 8) | 
            (ord($bKey[0]) & 0xFF);
        return $res & 0xFFFFFFFF;
    }

    /**
     * --- 获取区间节点系列 ---
     * @param int $min 最小值（含）
     * @param int $max 最大值（含）
     * @param string $pre 前导
     * @return string[]
     */
    public static function getRange($min, $max, $pre = '') {
        $ls = [];
        for ($i = $min; $i <= $max; ++$i) {
            $ls[] = $pre . $i;
        }
        return $ls;
    }

    /**
     * --- 添加到圆环 ---
     * @param array $circle 圆环
     * @param string|array $node node 节点名一个或多个
     * @param int $vcount 虚拟节点数量
     */
    public static function addToCircle(
        array &$circle,
        string|array $node,
        int $vcount = 300
    ) {
        if (is_string($node)) {
            $node = [$node];
        }
        foreach ($node as $v) {
            for ($i = 0; $i < $vcount; $i++) {
                $circle[self::hash($v . $i)] = $v;
            }
        }
    }

    /**
     * --- 获得一个最近的顺时针节点 ---
     * @param array $circle 圆环
     * @param string|int|float $key 为给定键取 Hash，取得顺时针方向上最近的一个虚拟节点对应的实际节点
     * @param array $keys keys，留空则自动从 circle 上取
     */
    public static function findInCircle(
        array &$circle,
        string|int|float $key,
        array $keys = []
    ): string | null {
        $count = count($keys);
        if ($count === 0) {
            $keys = array_keys($circle);
            $count = count($keys);
            sort($keys);
        }
        if ($count === 0) {
            return null;
        }
        if ($count === 1) {
            return $circle[$keys[0]];
        }
        $hashv = self::hash($key);
        if (isset($circle[$hashv])) {
            return $circle[$hashv];
        }
        /*
        SortedMap<Long, T> tailMap = circle.tailMap(hash); 
        hash = tailMap.isEmpty() ? circle.firstKey() : tailMap.firstKey();
        */
        foreach ($keys as $v) {
            if ((float)$v < $hashv) {
                continue;
            }
            return $circle[$v];
        }
        // --- 没找到 ---
        return $circle[$keys[0]];
    }

    /**
     * --- 获取当前的虚拟节点数量 ---
     */
    public function getVcount(): int {
        return $this->_vcount;
    }

    /**
     * --- 添加节点 ---
     * @param string|string[] $node 节点名一个或多个
     */
    public function add(string|array $node) {
        self::addToCircle($this->_circle, $node, $this->_vcount);
        $this->_keys = [];
    }

    /**
     * --- 移除节点 ---
     * @param string $node 节点名
     */
    public function remove($node) {
        if (is_string($node)) {
            $node = [$node];
        }
        foreach ($node as $v) {
            for ($i = 0; $i < $this->_vcount; $i++) {
                unset($this->_circle[self::hash($v . $i)]);
            }
        }
        $this->_keys = [];
    }

    /**
     * --- 获得一个最近的顺时针节点 ---
     * @param string|int|float $key 为给定键取 Hash，取得顺时针方向上最近的一个虚拟节点对应的实际节点
     */
    public function find(string|int|float $key) {
        if (count($this->_keys) === 0) {
            $this->_keys = array_keys($this->_circle);
            sort($this->_keys);
        }
        return self::findInCircle($this->_circle, $key, $this->_keys);
    }

    /**
     * --- 原数据迁移到新地址 ---
     * @param string|string[] $keys 原始数据 key 集
     * @param string|string[] $node 新增的节点一个或多个
     */
    public function migration($keys, $node) {
        $rtn = [];
        if (is_string($keys)) {
            $keys = [$keys];
        }
        // --- 获取老的 key 对应的 node ---
        $mapOld = [];
        foreach ($keys as $key) {
            $oldNode = $this->find($key);
            if (!$oldNode) {
                continue;
            }
            $mapOld[$key] = $oldNode;
        }
        $this->add($node);
        // --- 再逐一检测老的和新的的 node 是否一致 ---
        foreach ($keys as $key) {
            $newNode = $this->find($key);
            if (!$newNode) {
                continue;
            }
            if ($mapOld[$key] === $newNode) {
                continue;
            }
            $rtn[$key] = [
                'old' => $mapOld[$key],
                'new' => $newNode
            ];
        }
        return $rtn;
    }

}

