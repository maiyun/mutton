<?php

namespace C\lib {

    class Redis {

        /**
         * @var \Redis
         */
        private static $link = NULL;
        public static $index = NULL;

        /**
         * 连接数据库
         */
        public static function connect($host = NULL, $user = NULL, $pwd = NULL, $port = NULL) {

            $host = $host ? $host : RD_HOST;
            $user = $user ? $user : RD_USER;
            $pwd = $pwd ? $pwd : RD_PWD;
            $port = $port ? $port : RD_PORT;
            self::$index = RD_INDEX;

            if(RD_SIMULATOR) {
                self::$link = new RedisSimulator();
            } else {
                self::$link = new \Redis();
            }
            if($link = self::$link->connect($host, $port)) {
                if(self::$link->auth($user.':'.$pwd)) {
                    self::$link->select(self::$index);
                    return true;
                } else
                    return false;
            } else
                return $link;

        }

        public static function isConnect() {
            if(self::$link !== NULL)
                return self::$link->ping() == '+PONG' ? true : false;
            else return false;
        }

        public static function get($key) {
            if($v = self::$link->get($key)) {
                $a = @unserialize($v);
                if(is_array($a))
                    return $a;
                else
                    return $v;
            } else
                return false;
        }

        /**
         * @param string $key 设置 Redis 的 key
         * @param string $value 设置 Redis 的值
         * @param int $ttl 设置有效期,0为永久有效
         * @param string $mod 设置模式,空,nx（key不存在才建立）,xx（key存在才修改）
         * @return bool
         */
        public static function set($key = '', $value = '', $ttl = 0, $mod = '') {
            $opt = [];
            if($mod != '') $opt[] = $mod;
            if($ttl != 0) $opt['ex'] = $ttl+0;
            if(is_array($value)) $value = serialize($value);
            if(self::$link->set($key, $value, $opt))
                return true;
            else return false;
        }

        public static function incr($key, $num = 1) {
            if($num == 1)
                return self::$link->incr($key);
            else
                return self::$link->incrBy($key, $num);
        }

        public static function decr($key, $num = 1) {
            if($num == 1)
                return self::$link->decr($key);
            else
                return self::$link->decrBy($key, $num);
        }

        /**
         * 清除当前DB的所有的数据
         * @return bool
         */
        public static function delAll() {
            //self::$link->flushDB();
            return true;
        }

        // --- 删除某个 key ---
        public static function del($key) {
            self::$link->delete($key);
            return true;
        }

        // --- 底层方法 ---

        public static function ping() {
            return self::$link->ping();
        }

    }

}

