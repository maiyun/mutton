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

require ETC_PATH.'session.php';

/*
 * 模式分为：Db, Kv
 */

class Session {

    /* @var $_link IKv|Db */
    private static $_link = null;
    /* @var $_sql LSql */
    private static $_sql = null;

    /** @var string Session 在前端或 Kv 中储存的名前缀 */
    private static $_name = '';
    /** @var string 当前 Session 的 token */
    private static $_token = '';
    /** @var int Session 有效期 */
    private static $_ttl = 0;

    /**
     * @param IKv|Db $link
     * @param array $opt
     */
    public static function start($link, array $opt = []): void {
        $time = time();
        self::$_name = isset($opt['name']) ? $opt['name'] : SESSION_NAME;
        $ssl = isset($opt['ssl']) ? $opt['ssl'] : SESSION_SSL;
        self::$_ttl = isset($opt['ttl']) ? $opt['ttl'] : SESSION_TTL;

        if (isset($_POST[self::$_name])) {
            self::$_token = $_POST[self::$_name];
        } else if (isset($_COOKIE[self::$_name])) {
            self::$_token = $_COOKIE[self::$_name];
        }

        self::$_link = $link;
        if ($link instanceof Db) {
            self::$_sql = Sql::get();
            self::_gc();    // --- 执行 gc ---
        }

        // --- 初始化 Session 数组 ---
        $_SESSION = [];
        $needInsert = false;
        // --- 有 token 则查看 token 的信息是否存在
        if (self::$_token != '') {
            // --- 如果启用了内存加速则在内存找 ---
            if (self::$_link instanceof IKv) {
                // --- Kv ---
                if(($data = self::$_link->getJson(self::$_name . '_' . self::$_token)) === false) {
                    $needInsert = true;
                } else {
                    $_SESSION = $data;
                }
            } else {
                // --- 数据库 ---
                self::$_sql->select('*', 'session')->where([
                    'token' => self::$_token,
                    ['time_update', '>=', $time - self::$_ttl]
                ]);
                $ps = self::$_link->prepare(self::$_sql->getSql());
                $ps->execute(self::$_sql->getData());
                if ($data = $ps->fetch(PDO::FETCH_ASSOC)) {
                    $_SESSION = json_decode($data['data'], true);
                } else {
                    $needInsert = true;
                }
            }
        } else {
            // --- 全新的机子 ---
            $needInsert = true;
        }
        // --- 本来就该添加个新 Session ---
        // --- 内存和数据库里没找到的也该添加个新 Session ---
        // --- 数据库的 Session 已经过期加新 Session ---
        // --- 如果不存在不允许加新则返回错误 ---
        if ($needInsert) {
            if(self::$_link instanceof IKv) {
                do {
                    self::$_token = self::_random();
                } while (!self::$_link->set(self::$_name . '_' . self::$_token, [], self::$_ttl, 'nx'));
            } else {
                do {
                    self::$_token = self::_random();
                    self::$_sql->insert('session')->values([
                        'token' => self::$_token,
                        'data' => json_encode([]),
                        'time_update' => $time,
                        'time_add' => $time
                    ]);
                    $ps = self::$_link->prepare(self::$_sql->getSql());
                } while (!$ps->execute(self::$_sql->getData()));
            }
        }

        setcookie(self::$_name, self::$_token, $time + self::$_ttl, '/' ,'', $ssl, true);

        register_shutdown_function([self::class, '_update']);
    }

    /**
     * --- 页面整体结束时，要写入到 Redis 或 数据库 ---
     */
    public static function _update(): void {
        // --- 写入内存或数据库 ---
        if(self::$_link instanceof IKv) {
            self::$_link->set(self::$_name . '_' . self::$_token, $_SESSION, self::$_ttl);
        } else {
            self::$_sql->update('session', [
                'data' => json_encode($_SESSION),
                'time_update' => time()
            ])->where([
                'token' => self::$_token
            ]);
            $ps = self::$_link->prepare(self::$_sql->getSql());
            $ps->execute(self::$_sql->getData());
        }
    }

    /**
     * --- 根据情况清空 Db 状态下的 session 表垃圾数据 ---
     * --- 仅能在 Db 模式执行，本函数不进行判断是否是 Db 模式 ---
     */
    private static function _gc(): void {
        if(rand(0, 20) == 10) {
            self::$_sql->delete('session')->where([
                ['time_update', '<', time() - self::$_ttl]
            ]);
            $ps = self::$_link->prepare(self::$_sql->getSql());
            $ps->execute(self::$_sql->getData());
        }
    }

    /**
     * --- 返回随机数 ---
     * @return string
     */
    private static function _random(): string {
        $s = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $sl = strlen($s);
        $t = '';
        for ($i = 16; $i; --$i) {
            $t .= $s[rand(0, $sl - 1)];
        }
        return $t;
    }

}

