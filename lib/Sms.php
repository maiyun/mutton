<?php
/**
 * User: JianSuoQiYue
 * Date: 2017/11/18 16:39
 * Last: 2018-6-17 01:08
 */
declare(strict_types = 1);

/* For Qingmu Tongxun */

namespace lib;

require LIB_PATH.'Sms/qmsms.php';

require ETC_PATH.'sms.php';

class Sms {

    private static $_poll = [];

    /* @var $_link \QmSms */
    private $_link = NULL;

    public static function get(array $opt = [], ?string $name = 'main'): Sms {
        if ($name !== NULL) {
            if (isset(self::$_poll[$name])) {
                return self::$_poll[$name];
            } else {
                self::$_poll[$name] = new Sms($opt);
                return self::$_poll[$name];
            }
        } else {
            return new Sms($opt);
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

