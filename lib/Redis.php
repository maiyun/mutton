<?php
/**
 * User: JianSuoQiYue
 * Date: 2017/01/31 10:30
 * Last: 2018-6-16 01:11
 */
declare(strict_types = 1);

namespace M\lib {

    require ETC_PATH.'redis.php';

    class Redis {

        private static $_poll = [];

        /* @var \Redis $_link */
        private $_link = NULL;

        /**
         * @param string $name
         * @param array $opt
         * @return Redis
         * @throws \Exception
         */
        public static function get(string $name = 'main', array $opt = []): Redis {
            if (isset(self::$_poll[$name])) {
                return self::$_poll[$name];
            } else {
                $redis = new Redis();
                try {
                    $redis->connect($opt);
                    self::$_poll[$name] = $redis;
                    return self::$_poll[$name];
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }

        /**
         * @param array $opt
         * @return bool
         * @throws \Exception
         */
        public function connect(array $opt = []): bool {

            $host =  isset($opt['host']) ? $opt['host'] : RD_HOST;
            $user = isset($opt['user']) ? $opt['user'] : RD_USER;
            $pwd = isset($opt['pwd']) ? $opt['pwd'] : RD_PWD;
            $port = isset($opt['port']) ? $opt['port'] : RD_PORT;
            $index = isset($opt['index']) ? $opt['index'] : RD_INDEX;
            $simulator = isset($opt['simulator']) ? $opt['simulator'] : RD_SIMULATOR;

            if($simulator) {
                $this->_link = new RedisSimulator();
            } else {
                $this->_link = new \Redis();
            }
            if($link = $this->_link->connect($host, $port)) {
                if ($user != '' && $pwd != '') {
                    if ($this->_link->auth($user . ':' . $pwd)) {
                        $this->_link->select($index);
                        return true;
                    } else {
                        throw new \Exception('[Error] Redis auth failed.');
                    }
                } else {
                    $this->_link->select($index);
                    return true;
                }
            } else {
                throw new \Exception('[Error] Redis connect failed.');
            }

        }

        public function isConnect(): bool {
            if($this->_link !== NULL) {
                return $this->_link->ping() == '+PONG' ? true : false;
            } else {
                return false;
            }
        }

        /**
         * @param string $key
         * @return array|string|bool
         */
        public function getValue(string $key) {
            if($v = $this->_link->get($key)) {
                $a = @unserialize($v);
                if(is_array($a)) {
                    return $a;
                } else {
                    return $v;
                }
            } else {
                return false;
            }
        }

        /**
         * @param string $key 设置 Redis 的 key
         * @param array|string $value 设置 Redis 的值
         * @param int $ttl 设置有效期,0为永久有效
         * @param string $mod 设置模式: 空,nx（key不存在才建立）,xx（key存在才修改）
         * @return bool
         */
        public function setValue(string $key = '', $value = '', int $ttl = 0, string $mod = ''): bool {
            $opt = [];
            if($mod != '') {
                $opt[] = $mod;
            }
            if($ttl != 0) {
                $opt['ex'] = $ttl+0;
            }
            if(is_array($value)) {
                $value = serialize($value);
            }
            if($this->_link->set($key, $value, $opt)) {
                return true;
            } else {
                return false;
            }
        }

        public function incr(string $key, int $num = 1): int {
            if($num == 1) {
                return $this->_link->incr($key);
            } else {
                return $this->_link->incrBy($key, $num);
            }
        }

        public function decr(string $key, int $num = 1): int {
            if($num == 1) {
                return $this->_link->decr($key);
            } else {
                return $this->_link->decrBy($key, $num);
            }
        }

        /**
         * 清除当前DB的所有的数据，禁止使用，不安全
         * @return bool
         */
        public function delAll(): bool {
            //self::$link->flushDB();
            return true;
        }

        // --- 删除某个 key ---
        public function del(string $key): int {
            return $this->_link->delete($key);
        }

        // --- 底层方法 ---

        public function ping(): string {
            return $this->_link->ping();
        }

    }

}

