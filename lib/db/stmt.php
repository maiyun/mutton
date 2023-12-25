<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2023-8-25 20:25:30
 * Last: 2023-8-25 20:25:30
 */

declare(strict_types = 1);

namespace lib\Db;

use PDO;
use PDOStatement;

class Stmt {

    private PDOStatement $_stmt;

    public function __construct(PDOStatement $stmt) {
        $this->_stmt = $stmt;
    }

    /**
     * --- 处理值 ---
     */
    private function _parseVal(&$row, &$key) {
        $val = $row[$key];
        if (!is_string($val)) {
            return;
        }
        if ($val[0] === '{' && $val[strlen($val) - 1] === '}') {
            // --- 可能是 json ---
            $v = json_decode($val);
            if ($v) {
                $row[$key] = $v;
                return;
            }
        }
        $is = substr($val, 3, 3);
        if (!$is) {
            return;
        }
        $hex = bin2hex($is);
        if ($hex === '000101') {
            // --- point ---
            $p = unpack('x4/corder/ltype/dx/dy', $val);
            $row[$key] = [
                'x' => $p['x'],
                'y' => $p['y']
            ];
            return;
        }
        else if ($hex === '000103') {
            // --- polygon ---
            $p = unpack('x4/corder/ltype/lrings', $val);
            $offset = 13;
            $polygon = [];
            for ($i = 0; $i < $p['rings']; ++$i) {
                // --- 循环次数 ---
                $ring = [];
                $plen = unpack('llen', substr($val, $offset, 4))['len'];
                $offset += 4;
                for ($j = 0; $j < $plen; ++$j) {
                    $point = unpack('dx/dy', substr($val, $offset, 16));
                    $offset += 16;
                    $ring[] = $point;
                }
                $polygon[] = $ring;
            }
            $row[$key] = $polygon;
            return;
        }
    }

    public function fetch(int $mode = PDO::FETCH_BOTH): mixed {
        // --- 本函数涉及 GIS 部分参考解析文档 ---
        // --- https://www.ibm.com/docs/en/db2woc?topic=formats-well-known-binary-wkb-format ---
        $row = $this->_stmt->fetch($mode);
        if ($row === false) {
            return false;
        }
        foreach ($row as $key => $val) {
            $this->_parseVal($row, $key);
        }
        return $row;
    }

    /**
     * --- fetch 为一个对象 ---
     */
    public function fetchObject(string|null $class = "stdClass", array $constructorArgs = []): object|false {
        $obj = $this->_stmt->fetchObject($class, $constructorArgs);
        if ($obj === null) {
            return null;
        }
        foreach ($obj as $key => $val) {
            var_dump('x2', $this->_stmt->getColumnMeta($key));
            $this->_parseVal($obj, $key);
        }
        return $obj;
    }

    /**
     * --- 执行预定义 sql，传入替换 data ---
     */
    public function execute(array|null $params = null): bool {
        return $this->_stmt->execute($params);
    }

    /**
     * --- 获取列信息 ---
     */
    public function getColumnMeta(int $column): array|false {
        return $this->_stmt->getColumnMeta($column);
    }

    /**
     * --- 获取列长度 ---
     */
    public function columnCount(): int {
        return $this->_stmt->columnCount();
    }

    /**
     * --- 获取行数 ---
     */
    public function rowCount(): int {
        return $this->_stmt->rowCount();
    }

    /**
     * --- 获取错误信息 ---
     */
    public function errorInfo(): array {
        return $this->_stmt->errorInfo();
    }

}
