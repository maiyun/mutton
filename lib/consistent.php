<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2022-09-01 21:56:06
 * Last: 2022-09-01 21:56:06, 2022-09-03 00:56:29
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
     * @param string $key 要查找的key
     * @param array $nodes node 列表
     * @param int $vcount 虚拟节点数量
     */
    public static function fast($key, $nodes, $vcount = 300) {
        $cons = new Consistent($vcount);
        $cons->add($nodes);
        return $cons->find($key);
    }

    /**
     * --- hash 函数 ---
     * @param string|int|float $val 要 hash 的值
     */
    public static function hash(string|int|float $val) {
        if (is_int($val) || is_float($val)) {
            $val = $val . '';
        }
        $bKey = md5($val);
        $res = ((ord($bKey[3]) & 0xFF) << 24) | 
            ((ord($bKey[2]) & 0xFF) << 16) | 
            ((ord($bKey[1]) & 0xFF) << 8) | 
            (ord($bKey[0]) & 0xFF);
        return $res & 0xFFFFFFFF;
    }

    /**
     * --- 获取当前的虚拟节点数量 ---
     */
    public function getVcount(): int {
        return $this->_vcount;
    }

    /**
     * 添加节点
     * @param string|string[] node 节点名一个或多个
     */
    public function add($node) {
        if (is_string($node)) {
            $node = [$node];
        }
        foreach ($node as $v) {
            for ($i = 0; $i < $this->_vcount; $i++) {
                $this->_circle[self::hash($v . $i)] = $v;
            }
        }
        $this->_keys = [];
    }

    /**
     * 移除节点
     * @param string node 节点名
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
     * 获得一个最近的顺时针节点
     * @param string key 为给定键取Hash，取得顺时针方向上最近的一个虚拟节点对应的实际节点
     */
    public function find($key) {
        if (count($this->_keys) === 0) {
            $this->_keys = array_keys($this->_circle);
            sort($this->_keys);
        }
        $count = count($this->_keys);
        if ($count === 0) {
            return null;
        }
        if ($count === 1) {
            return $this->_circle[$this->_keys[0]];
        }
        $hash = self::hash($key);
        if (isset($this->_circle[$hash])) {
            return $this->_circle[$hash];
        }
        /*
        SortedMap<Long, T> tailMap = circle.tailMap(hash); 
        hash = tailMap.isEmpty() ? circle.firstKey() : tailMap.firstKey();
        */
        foreach ($this->_keys as $v) {
            if ($v < $hash) {
                continue;
            }
            return $this->_circle[$v];
        }
        // --- 没找到 ---
        return $this->_circle[$this->_keys[0]];
    }

}

