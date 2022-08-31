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
 * Last: 2019-12-16 14:52:54, 2020-3-28 12:51:43, 2020-4-17 14:54:05, 2022-3-24 23:59:09, 2022-08-31 17:47:40
 */

namespace lib;

use lib\Kv\IKv;
use lib\Kv\Redis;
use lib\Kv\RedisSimulator;

require ETC_PATH.'kv.php';

class Kv {

    // --- 核心类型 ---
    const REDIS = 'redis';
    const REDIS_SIMULATOR = 'redis-simulator';

    /**
     * @param string $core
     * @return IKv
     */
    public static function get(string $core = self::REDIS) {
        // $class = 'lib\\kv\\' . $core;
        if ($core === self::REDIS) {
            return new Redis();
        }
        else {
            return new RedisSimulator();
        }
    }

}

