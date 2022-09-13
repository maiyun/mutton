<?php
/*
 * --- mysql ---
CREATE TABLE `session` (
  `id` int (10) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar (16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `time_update` int (10) unsigned NOT NULL,
  `time_add` int (10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `time_update` (`time_update`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

 * --- sqlite ---
CREATE TABLE `session` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `token` VARCHAR(16) UNIQUE NOT NULL,
  `data` TEXT NOT NULL,
  `time_update` INT(10) NOT NULL,
  `time_add` INT(10) NOT NULL
);
CREATE INDEX `time_update` ON `session` (`time_update`);
*/

/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2015/05/25 19:56
 * Last: 2019-1-29 17:18:25, 2020-01-04 17:38:33, 2020-3-29 20:38:57, 2020-11-20 14:25:14, 2022-09-01 16:21:08
 */
declare(strict_types = 1);

namespace lib;

use lib\Kv\IKv;
use PDO;
use PDOException;
use sys\Ctr;

use function sys\log;

require ETC_PATH . 'session.php';

/*
 * 模式分为：Db, Kv
 */

class Session {

    /* @var $_link IKv|Db */
    private $_link;
    /* @var $_sql LSql */
    private $_sql = null;

    /** @var string Session 在前端或 Kv 中储存的名前缀 */
    private $_name;
    /** @var string 当前 Session 的 token */
    private $_token = '';
    /** @var int Session 有效期 */
    private $_ttl;

    /**
     * Session constructor.
     * @param Ctr $ctr 模型实例
     * @param IKv|Db $link Kv 或 Db 实例
     * @param bool $auth 设为 true 则优先从头 Authorization 或 post _auth 值读取 token
     * @param array $opt name, ttl, ssl, sqlPre
     */
    public function __construct(Ctr $ctr, $link, bool $auth = false, array $opt = []) {
        $time = time();
        $ssl = isset($opt['ssl']) ? $opt['ssl'] : SESSION_SSL;
        $pre = isset($opt['sqlPre']) ? $opt['sqlPre'] : null;
        $this->_name = isset($opt['name']) ? $opt['name'] : SESSION_NAME;
        $this->_ttl = isset($opt['ttl']) ? $opt['ttl'] : SESSION_TTL;

        if ($auth) {
            if (($a = $ctr->getAuthorization()) && ($a['user'] === 'token')) {
                $this->_token = $a['pwd'];
            }
        }
        if (($this->_token === '') && isset($_COOKIE[$this->_name])) {
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
                if (($data = $this->_link->getJson('sess-' . $this->_name . '_' . $this->_token)) === null) {
                    $needInsert = true;
                }
                else {
                    $_SESSION = $data;
                }
            }
            else {
                // --- 数据库 ---
                $this->_sql->select('*', 'session')->where([
                    ['time_update', '>=', $time - $this->_ttl],
                    'token' => $this->_token
                ]);
                $ps = $this->_link->prepare($this->_sql->getSql());
                $ps->execute($this->_sql->getData());
                if ($data = $ps->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION = json_decode($data['data'], true);
                }
                else {
                    $needInsert = true;
                }
            }
            $ctr->setPrototypeRef('_session', $_SESSION);
        }
        else {
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
                    $this->_token = Core::random(16, Core::RANDOM_LUN);
                } while (!$this->_link->set('sess-' . $this->_name . '_' . $this->_token, [], $this->_ttl, 'nx'));
            }
            else {
                $count = 0;
                while (true) {
                    if ($count === 5) {
                        return false;
                    }
                    $this->_token = Core::random(16, Core::RANDOM_LUN);
                    $this->_sql->insert('session')->values([
                        'token' => $this->_token,
                        'data' => json_encode([]),
                        'time_update' => $time,
                        'time_add' => $time
                    ]);
                    $ps = $this->_link->prepare($this->_sql->getSql());
                    ++$count;
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
            }
        }

        Core::setCookie($this->_name, $this->_token, [
            'ttl' => $this->_ttl,
            'ssl' => $ssl
        ]);

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
     * --- 获取当前的 cookie 的 name 值 ---
     */
    public function getName(): string {
        return $this->_name;
    }

    /**
     * --- 页面整体结束时，要写入到 Redis 或 数据库 ---
     */
    public function __update(): void {
        if ($this->_link instanceof IKv) {
            $this->_link->set('sess-' . $this->_name . '_' . $this->_token, $_SESSION, $this->_ttl);
        }
        else {
            $this->_sql->update('session', [
                'data' => json_encode($_SESSION),
                'time_update' => time()
            ])->where([
                'token' => $this->_token
            ]);
            $ps = $this->_link->prepare($this->_sql->getSql());
            try {
                $ps->execute($this->_sql->getData());
            }
            catch (PDOException $e) {
                log('[Session][__update]' . $e->getMessage(), '-error');
            }
        }
    }

    /**
     * --- 根据情况清空 Db 状态下的 session 表垃圾数据 ---
     * --- 仅能在 Db 模式执行，本函数不进行判断是否是 Db 模式 ---
     */
    private function _gc(): void {
        if(rand(0, 20) !== 10) {
            return;
        }
        $this->_sql->delete('session')->where([
            ['time_update', '<', time() - $this->_ttl]
        ]);
        $ps = $this->_link->prepare($this->_sql->getSql());
        try {
            $ps->execute($this->_sql->getData());
        }
        catch (PDOException $e) {
            log('[Session][_gc]' . $e->getMessage(), '-error');
        }
    }

}

