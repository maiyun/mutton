<?php
/*
CREATE TABLE if not exists `scan` (
    `id` int (10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `token` varchar (32) BINARY NOT NULL,
    `data` text NOT NULL,
    `time_update` int (10) UNSIGNED NOT NULL,
    `time_add` int (10) UNSIGNED NOT NULL,
    `time_exp` int (10) UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    unique `token` USING btree (`token`)
) ENGINE = innodb DEFAULT CHARACTER SET = "utf8mb4" COLLATE = "utf8mb4_general_ci"
*/

/**
 * Project: Mutton, User: Jiansuo Qiyue
 * CONF - {"ver":"0.1","folder":false} - END
 * Date: 2020-11-21 15:38:01
 * Last: 2020-11-21 15:38:04, 2020-11-22 22:12:02, 2022-3-25 14:31:38
 */
declare(strict_types = 1);

namespace lib;

use PDO;
use PDOException;
use sys\Ctr;

class Scan {

    /* @var $_link Db */
    private $_link;
    /* @var $_sql LSql */
    private $_sql;
    /** @var $_ctr Ctr */
    private $_ctr;

    /** @var $_token string */
    private $_token = null;
    /** @var int 有效期，默认 5 分钟 */
    private $_exp = 60 * 5;

    public function __construct(Ctr $ctr, Db $link, string $token = null, int $exp = null, string $sqlPre = null) {
        if ($exp) {
            $this->_exp = $exp;
        }
        $this->_ctr = $ctr;
        $this->_link = $link;
        $this->_sql = Sql::get($sqlPre);
        if ($token) {
            $this->_token = $token;
        }
        else {
            $this->_token = $this->createToken();
        }
    }

    /**
     * --- 创建 Scan 对象 ---
     * @param Ctr $ctr 模型实例
     * @param Db $link
     * @param string|null $token
     * @param int|null $exp
     * @param string|null $sqlPre
     * @return Scan
     */
    public static function get(Ctr $ctr, Db $link, string $token = null, int $exp = null, string $sqlPre = null): Scan {
        return new Scan($ctr, $link, $token, $exp, $sqlPre);
    }

    /** @var int|null 二维码剩余有效时间 */
    private $timeLeft = null;
    /**
     * --- 生成二维码处的轮询，检查是否被扫码、被录入数据 ---
     * @return mixed -3 系统错误 -2 token 不存在或已过期 -1 无操作, 0 已扫码, 其他返回为存的数据并结束轮询
     */
    public function poll() {
        $time = time();
        $this->_sql->select('*', 'scan')->where([
            'token' => $this->_token,
            ['time_exp', '>=', $time]
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
            // --- 创建 ---
            return -2;
        }
        // --- 存在，判断是否被扫码，以及是否被注入数据 ---
        $this->timeLeft = $data['time_exp'] - $time;
        if ($data['data'] !== '') {
            $this->_sql->delete('scan')->where([
                'id' => $data['id']
            ]);
            return json_decode($data['data'], true);
        }
        else if ($data['time_update'] > 0) {
            return 0;
        }
        else {
            return -1;
        }
    }

    /**
     * --- 创建 token ---
     * @param int|null $exp 有效期，默认 5 分钟
     * @return string|boolean
     */
    public function createToken(int $exp = null) {
        $this->_gc();
        if (!$exp) {
            $exp = $this->_exp;
        }
        $time = time();
        $count = 0;
        while (true) {
            if ($count === 5) {
                return false;
            }
            $this->_token = $this->_ctr->_random(32, Ctr::RANDOM_LUN);
            $this->_sql->insert('scan')->values([
                'token' => $this->_token,
                'data' => '',
                'time_update' => '0',
                'time_add' => $time,
                'time_exp' => $time + $exp
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
        return $this->_token;
    }

    /**
     * --- 获取当前 token ---
     * @return string
     */
    public function getToken() {
        return $this->_token;
    }

    /**
     * --- 设置有效期，设置后的新 token 被创建有效 ---
     * @param int $exp
     */
    public function setExpire(int $exp) {
        $this->_exp = $exp;
    }

    /**
     * --- 获取设置的有效期 ---
     * @return int
     */
    public function getExpire() {
        return $this->_exp;
    }

    /**
     * --- 获取当前 token 可扫剩余有效期 ---
     * @return int|null
     */
    public function getTimeLeft() {
        return $this->timeLeft;
    }

    /**
     * --- 对 token 执行访问操作，通常用户扫码后展示的网页所调用 ---
     * @param Db $link
     * @param string $token
     * @param string|null $sqlPre
     * @return bool
     */
    public static function scanned(Db $link, string $token, string $sqlPre = null) {
        $time = time();
        $sql = Sql::get($sqlPre);
        $sql->update('scan', [
            'time_update' => $time
        ])->where([
            'token' => $token,
            'time_update' => '0',
            ['time_exp', '>=', $time]
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
        return false;
    }

    /**
     * --- 将数据写入 token ---
     * @param Db $link
     * @param string $token
     * @param $data
     * @param string|null $sqlPre
     * @return bool
     */
    public static function setData(Db $link, string $token, $data, string $sqlPre = null) {
        if (is_int($data)) {
            if ($data >= -3 && $data <= 1) {
                return false;
            }
        }
        $time = time();
        $sql = Sql::get($sqlPre);
        $sql->update('scan', [
            'data' => json_encode($data)
        ])->where([
            'token' => $token,
            ['time_update', '>', '0'],
            ['time_exp', '>=', $time]
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
        return false;
    }

    /**
     * --- 根据情况清空 Db 状态下的 scan 表垃圾数据 ---
     */
    private function _gc(): void {
        if(rand(0, 10) !== 5) {
            return;
        }
        $this->_sql->delete('scan')->where([
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

