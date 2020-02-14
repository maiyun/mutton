<?php
/*
 * --- Mysql ---
CREATE TABLE `session` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `token` varchar(255) NOT NULL UNIQUE KEY,
  `data` text NOT NULL,
  `time_update` int(10) UNSIGNED NOT NULL,
  `time_add` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

 * --- Sqlite ---
CREATE TABLE `session` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `token` VARCHAR(255) UNIQUE NOT NULL,
  `data` TEXT NOT NULL,
  `time_update` INT(10) NOT NULL,
  `time_add` INT(10) NOT NULL
);
*/

/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.1","folder":false} - END
 * Date: 2015/05/25 19:56
 * Last: 2019-1-29 17:18:25, 2020-01-04 17:38:33
 */
declare(strict_types = 1);

namespace lib;

use lib\Kv\IKv;
use PDO;
use sys\Ctr;

require ETC_PATH.'session.php';

/*
 * 模式分为：Db, Kv
 */

class Session {

    /* @var $_link IKv|Db */
    private $_link = null;
    /* @var $_sql LSql */
    private $_sql = null;

    /** @var string Session 在前端或 Kv 中储存的名前缀 */
    private $_name = '';
    /** @var string 当前 Session 的 token */
    private $_token = '';
    /** @var int Session 有效期 */
    private $_ttl = 0;

    /**
     * Session constructor.
     * @param Ctr $ctr 模型实例
     * @param IKv|Db $link Kv 或 Db 实例
     * @param bool $auth 设为 true 则从头 Authorization 或 post _auth 值读取 token
     * @param array $opt name, ttl, ssl
     */
    public function __construct(&$ctr, $link, bool $auth = false, array $opt = []) {
        $time = time();
        $ssl = isset($opt['ssl']) ? $opt['ssl'] : SESSION_SSL;
        $pre = isset($opt['pre']) ? $opt['pre'] : null;
        $this->_name = isset($opt['name']) ? $opt['name'] : SESSION_NAME;
        $this->_ttl = isset($opt['ttl']) ? $opt['ttl'] : SESSION_TTL;

        if ($auth) {
            if (($a = $ctr->_getAuthorization()) && ($a['user'] === 'token')) {
                $this->_token = $a['pwd'];
            }
        } else if (isset($_COOKIE[$this->_name])) {
            $this->_token = $_COOKIE[$this->_name];
        }

        $this->_link = $link;
        if ($link instanceof Db) {
            $this->_sql = Sql::get($pre);
            $this->_gc();    // --- 执行 gc ---
        }

        // --- 初始化 Session 数组 ---
        $_SESSION = [];
        $needInsert = false;
        // --- 有 token 则查看 token 的信息是否存在
        if ($this->_token !== '') {
            // --- 如果启用了内存加速则在内存找 ---
            if ($this->_link instanceof IKv) {
                // --- Kv ---
                if(($data = $this->_link->getJson($this->_name . '_' . $this->_token)) === false) {
                    $needInsert = true;
                } else {
                    $_SESSION = $data;
                }
            } else {
                // --- 数据库 ---
                $this->_sql->select('*', 'session')->where([
                    'token' => $this->_token,
                    ['time_update', '>=', $time - $this->_ttl]
                ]);
                $ps = $this->_link->prepare($this->_sql->getSql());
                $ps->execute($this->_sql->getData());
                if ($data = $ps->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION = json_decode($data['data'], true);
                } else {
                    $needInsert = true;
                }
            }
            $ctr->_session = &$_SESSION;
        } else {
            // --- 全新的机子 ---
            $needInsert = true;
        }
        // --- 本来就该添加个新 Session ---
        // --- 内存和数据库里没找到的也该添加个新 Session ---
        // --- 数据库的 Session 已经过期加新 Session ---
        // --- 如果不存在不允许加新则返回错误 ---
        if ($needInsert) {
            if ($this->_link instanceof IKv) {
                do {
                    $this->_token = $ctr->_random(16, Ctr::RANDOM_LUN);
                } while (!$this->_link->set($this->_name . '_' . $this->_token, [], $this->_ttl, 'nx'));
            } else {
                do {
                    $this->_token = $ctr->_random(16, Ctr::RANDOM_LUN);
                    $this->_sql->insert('session')->values([
                        'token' => $this->_token,
                        'data' => json_encode([]),
                        'time_update' => $time,
                        'time_add' => $time
                    ]);
                    $ps = $this->_link->prepare($this->_sql->getSql());
                } while (!$ps->execute($this->_sql->getData()));
            }
        }

        setcookie($this->_name, $this->_token, $time + $this->_ttl, '/' ,'', $ssl, true);

        register_shutdown_function([$this, '__update']); // self::class
    }

    /**
     * --- 获取当前的 token 值 ---
     * @return string
     */
    public function getToken(): string {
        return $this->_token;
    }

    /**
     * --- 页面整体结束时，要写入到 Redis 或 数据库 ---
     */
    public function __update(): void {
        // --- 写入内存或数据库 ---
        if($this->_link instanceof IKv) {
            $this->_link->set($this->_name . '_' . $this->_token, $_SESSION, $this->_ttl);
        } else {
            $this->_sql->update('session', [
                'data' => json_encode($_SESSION),
                'time_update' => time()
            ])->where([
                'token' => $this->_token
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            $ps->execute($this->_sql->getData());
        }
    }

    /**
     * --- 根据情况清空 Db 状态下的 session 表垃圾数据 ---
     * --- 仅能在 Db 模式执行，本函数不进行判断是否是 Db 模式 ---
     */
    private function _gc(): void {
        if(rand(0, 20) == 10) {
            $this->_sql->delete('session')->where([
                ['time_update', '<', time() - $this->_ttl]
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            $ps->execute($this->_sql->getData());
        }
    }

}

