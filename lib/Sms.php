<?php
/**
 * User: JianSuoQiYue
 * Date: 2017/11/18 16:39
 * Last: 2018-6-17 01:08
 */
declare(strict_types = 1);

/* For Qingmu Tongxun */

namespace M\lib {

    require LIB_PATH.'Sms/qmsms.php';

    class Sms {

        private static $_poll = [];

        /* @var $_link \QmSms */
        private $_link = NULL;

        public static function get(string $name = 'main', array $opt = []): Db {
            if (isset(self::$_poll[$name])) {
                return self::$_poll[$name];
            } else {
                $sms = new Sms($opt);
                self::$_poll[$name] = $sms;
                return self::$_poll[$name];
            }
        }

        public function __construct(array $opt = []) {
            $srv = isset($opt['srv']) ? $opt['srv'] : SMS_SRV;
            $usr = isset($opt['usr']) ? $opt['usr'] : SMS_USER;
            $tok = isset($opt['tok']) ? $opt['tok'] : SMS_TOKEN;

            $this->_link = new \QmSms($srv, $usr, $tok);
        }

        public function send($phone, $body, $sign = '0', $template = '0') {
            return $this->_link->send($phone, $body, $sign, $template);
        }

        public function sendMarket($phone, $sign, $template) {
            return $this->_link->sendMarket($phone, $sign, $template);
        }

        public function task($name, $phone, $sign, $template) {
            return $this->_link->task($name, $phone, $sign, $template);
        }

    }

}

