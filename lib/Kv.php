<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.2","folder":true} - END
 * Date: 2019-12-16 14:52:51
 * Last: 2019-12-16 14:52:54, 2020-3-28 12:51:43
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

