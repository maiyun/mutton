<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {
    "ver": "0.3",
    "folder": true,
    "url": {
        "https://github.com/maiyun/Mutton/raw/{ver}/lib/Kv/IKv.php": {
            "mirror-cn": "https://gitee.com/MaiyunNET/Mutton/raw/{ver}/lib/Kv/IKv.php",
            "action": "down",
            "save": "IKv.php"
        },
        "https://github.com/maiyun/Mutton/raw/{ver}/lib/Kv/Memcached.php": {
            "mirror-cn": "https://gitee.com/MaiyunNET/Mutton/raw/{ver}/lib/Kv/Memcached.php",
            "action": "down",
            "save": "Memcached.php"
        },
        "https://github.com/maiyun/Mutton/raw/{ver}/lib/Kv/Redis.php": {
            "mirror-cn": "https://gitee.com/MaiyunNET/Mutton/raw/{ver}/lib/Kv/Redis.php",
            "action": "down",
            "save": "Redis.php"
        },
        "https://github.com/maiyun/Mutton/raw/{ver}/lib/Kv/RedisSimulator.php": {
            "mirror-cn": "https://gitee.com/MaiyunNET/Mutton/raw/{ver}/lib/Kv/RedisSimulator.php",
            "action": "down",
            "save": "RedisSimulator.php"
        }
    }
} - END
 * Date: 2019-12-16 14:52:51
 * Last: 2019-12-16 14:52:54, 2020-3-28 12:51:43, 2020-4-17 14:54:05
 */

namespace lib;

use lib\Kv\IKv;

require ETC_PATH.'kv.php';

class Kv {

    // --- 核心类型 ---
    const REDIS = 'Redis';
    const REDIS_SIMULATOR = 'RedisSimulator';
    const MEMCACHED = 'Memcached';

    /**
     * @param string $core
     * @return IKv
     */
    public static function get(string $core) {
        $class = 'lib\\Kv\\' . $core;
        return new $class();
    }

}

