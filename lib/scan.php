<?php
/*
CREATE TABLE IF NOT EXISTS `scan` (
    `id` int (10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` char (32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    `data` text NOT NULL,
    `time_update` int (10) UNSIGNED NOT NULL,
    `time_add` int (10) UNSIGNED NOT NULL,
    `time_exp` int (10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`) USING btree,
    KEY `time_update` (`time_update`),
    KEY `time_exp` (`time_exp`)
) ENGINE = InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
*/

/**
 * Project: Mutton, User: Jiansuo Qiyue
 * Date: 2020-11-21 15:38:01
 * Last: 2020-11-21 15:38:04, 2020-11-22 22:12:02, 2022-3-25 14:31:38
 */
declare(strict_types = 1);

namespace lib;

use lib\Kv\IKv;
use PDO;
use PDOException;

class Scan {

    /* @var $_link Db|Kv */
    private $_link;

    /* @var $_sql LSql */
    private $_sql = null;

    /** @var string --- 表名或者 kv 里 key 的前缀 --- */
    private $_name = 'scan';

    /** @var $_token string */
    private $_token = null;

    /** @var int --- 有效期，默认 5 分钟 --- */
    private $_ttl = 60 * 5;

    /**
     * --- opt: ttl, sqlPre, name (表名或 kv 前缀) ---
     */
    public function __construct(Db|IKv $link, string $token = null, $opt = []) {
        if (isset($opt['ttl'])) {
            $this->_ttl = $opt['ttl'];
        }
        if (isset($opt['name'])) {
            $this->_name = $opt['name'];
        }
        $this->_link = $link;
        if ($link instanceof Db) {
            $this->_sql = Sql::get(isset($opt['sqlPre']) ? $opt['sqlPre'] : null);
        }
        if ($token) {
            $this->_token = $token;
        }
    }

    /**
     * --- 创建 Scan 对象 ---
     * @param Db|IKv $link
     * @param $token Token
     * @param $opt ttl, sqlPre, name (表名或 kv 前缀)
     * @return Scan
     */
    public static function get(Db|IKv $link, string $token = null, $opt = []): Scan {
        $scan = new Scan($link, $token, $opt);
        if (!$token) {
            $scan->createToken();
        }
        return $scan;
    }

    /** @var int|null --- 二维码剩余有效时间 --- */
    private $_timeLeft = null;

    /**
     * --- 生成二维码处的轮询，检查是否被扫码、被录入数据 ---
     * @return mixed -3 系统错误 -2 token 不存在或已过期 -1 无操作, 0 已扫码, 其他返回为存的数据并结束轮询
     */
    public function poll() {
        if (!$this->_token) {
            return -3;
        }
        $time = time();
        if ($this->_link instanceof Db) {
            // --- Db ---
            $this->_sql->select('*', $this->_name)->where([
                'token' => $this->_token,
                ['time_exp', '>', $time]
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            try {
                $ps->execute($this->_sql->getData());
            }
            catch (PDOException $e) {
                // --- 出错 ---
                return -3;
            }
            if (!($data = $ps->fetch(PDO::FETCH_ASSOC))) {
                // --- 不存在或过期 ---
                return -2;
            }
            // --- 存在，判断是否被扫码，以及是否被写入数据 ---
            $this->_timeLeft = $data['time_exp'] - $time;
            if ($data['data'] !== '') {
                // --- 已经写入数据了，删除数据库条目并返回写入的数据内容 ---
                $this->_sql->delete($this->_name)->where([
                    'id' => $data['id']
                ]);
                $rtn = json_decode($data['data'], true);
                if (!$rtn) {
                    return -3;
                }
                return $rtn;
            }
            else if ($data['time_update'] > 0) {
                // --- 已被扫描 ---
                return 0;
            }
            else {
                // --- 未扫描 ---
                return -1;
            }
        }
        else {
            // --- Kv ---
            $data = $this->_link->getJson('scan-' . $this->_name . '_' . $this->_token);
            if ($data === null) {
                // --- 不存在或过期 ---
                return -2;
            }
            $ttl = $this->_link->ttl('scan-' . $this->_name . '_' . $this->_token);
            if ($ttl === null) {
                return -3;
            }
            $this->_timeLeft = $ttl;
            if ($data['data'] !== null) {
                // --- 已经写入数据了，删除数据库条目并返回写入的数据内容 ---
                $this->_link->del('scan-' . $this->_name . '_' . $this->_token);
                return $data;
            }
            else if ($data['time_update'] > 0) {
                // --- 已被扫描 ---
                return 0;
            }
            else {
                // --- 未扫描 ---
                return -1;
            }
        }
    }

    /**
     * --- 创建 token，直接应用到本类 ---
     * @return boolean
     */
    public function createToken() {
        $this->_gc();
        $time = time();
        $count = 0;
        while (true) {
            if ($count === 5) {
                return false;
            }
            $this->_token = Core::random(32, Core::RANDOM_LUN);
            if ($this->_link instanceof Db) {
                // --- Db ---
                $this->_sql->insert($this->_name)->values([
                    'token' => $this->_token,
                    'data' => '',
                    'time_update' => '0',
                    'time_add' => $time,
                    'time_exp' => $time + $this->_ttl
                ]);
                $ps = $this->_link->prepare($this->_sql->getSql());
                try {
                    $ps->execute($this->_sql->getData());
                    break;
                }
                catch (PDOException $e) {
                    if ($e->errorInfo[0] !== '23000') {
                        return false;
                    }
                }
            }
            else {
                // --- Kv ---
                if ($this->_link->set('scan-' . $this->_name . '_' . $this->_token, [
                    'time_update' => 0,
                    'data' => null
                ], $this->_ttl, 'nx')) {
                    break;
                }
            }
            ++$count;
        }
        return true;
    }

    /**
     * --- 获取当前 token ---
     * @return string|null
     */
    public function getToken() {
        return $this->_token;
    }

    /**
     * --- 设置有效期，设置后的新 token 被创建有效 ---
     * @param int $ttl
     */
    public function setTTL(int $ttl) {
        $this->_ttl = $ttl;
    }

    /**
     * --- 获取设置的有效期，设置后的新 token 被创建有效 ---
     * @return int
     */
    public function getTTL() {
        return $this->_ttl;
    }

    /**
     * --- 获取当前 token 可扫剩余有效期 ---
     * @return int|null
     */
    public function getTimeLeft() {
        return $this->_timeLeft;
    }

    /**
     * --- 对 token 执行访问操作，通常用户扫码后展示的网页所调用，代表已扫码 ---
     * @param Db|IKv $link
     * @param $token 必填
     * @param $opt sqlPre, name (表名或 kv 前缀)
     * @return bool
     */
    public static function scanned(Db|IKv $link, string $token, $opt = []) {
        $time = time();
        $name = isset($opt['name']) ? $opt['name'] : 'scan';
        if ($link instanceof Db) {
            // --- Db ---
            $sql = Sql::get(isset($opt['sqlPre']) ? $opt['sqlPre'] : null);
            $sql->update($name, [
                'time_update' => $time
            ])->where([
                'token' => $token,
                'time_update' => '0',
                ['time_exp', '>', $time]
            ]);
            $ps = $link->prepare($sql->getSql());
            try {
                $ps->execute($sql->getData());
            }
            catch (PDOException $e) {
                return false;
            }
            if ($ps->rowCount() > 0) {
                return true;
            }
        }
        else {
            // --- Kv ---
            $ldata = $link->getJson('scan-' . $name . '_' . $token);
            if ($ldata === null) {
                return false;
            }
            if ($ldata['time_update'] > 0) {
                // --- 已经被扫码过了 ---
                return false;
            }
            $ldata['time_update'] = $time;
            $ttl = $link->ttl('scan-' . $name . '_' . $token);
            if ($ttl === null) {
                return false;
            }
            return $link->set('scan-' . $name . '_' . $token, $ldata, $ttl + 1, 'xx');
        }
        return false;
    }

    /**
     * --- 将数据写入 token，通常在客户的逻辑下去写，服务器会 poll 到 ---
     * @param Db|IKv $link
     * @param string $token
     * @param $data
     * @param $opt sqlPre, name (表名或 kv 前缀)
     * @return bool
     */
    public static function setData(Db|IKv $link, string $token, $data, $opt = []) {
        if (is_int($data)) {
            if ($data >= -3 && $data <= 1) {
                return false;
            }
        }
        $time = time();
        $name = isset($opt['name']) ? $opt['name'] : 'scan';
        if ($link instanceof Db) {
            // --- Db ---
            $sql = Sql::get(isset($opt['sqlPre']) ? $opt['sqlPre'] : null);
            $sql->update($name, [
                'data' => json_encode($data)
            ])->where([
                'token' => $token,
                ['time_update', '>', '0'],
                ['time_exp', '>', $time]
            ]);
            $ps = $link->prepare($sql->getSql());
            try {
                $ps->execute($sql->getData());
            }
            catch (PDOException $e) {
                return false;
            }
            if ($ps->rowCount() > 0) {
                return true;
            }
        }
        else {
            // --- Kv ---
            $ldata = $link->getJson('scan-' . $name . '_' . $token);
            if ($ldata === null) {
                return false;
            }
            if ($ldata['time_update'] === 0) {
                // --- 还未被扫码，无法操作 ---
                return false;
            }
            $ttl = $link->ttl('scan-' . $name . '_' . $token);
            if ($ttl === null) {
                return false;
            }
            $ldata['data'] = $data;
            return $link->set('scan-' . $name . '_' . $token, $ldata, $ttl + 1, 'xx');
        }
        return false;
    }

    /**
     * --- 根据情况清空 Db 状态下的 scan 表垃圾数据 ---
     */
    private function _gc(): void {
        if ($this->_link instanceof IKv) {
            return;
        }
        if(rand(0, 10) !== 5) {
            return;
        }
        $this->_sql->delete($this->_name)->where([
            ['time_exp', '<', time()]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            // --- GC 出错 ---
        }
    }

}

