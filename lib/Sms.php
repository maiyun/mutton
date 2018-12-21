<?php
/**
 * User: JianSuoQiYue
 * Date: 2017/11/18 16:39
 * Last: 2018-12-20 15:34:57
 */
declare(strict_types = 1);

/* For BrSms */

/**
 * 本类请先不要使用，本次 commit 还未更新本类，以实际 release 版为准
 */

namespace lib;

require LIB_PATH.'Sms/brsms.php';

require ETC_PATH.'sms.php';

class Sms {

    /* @var $_link \BrSms */
    private $_link = NULL;

    public static function get(array $opt = []): Sms {
        return new Sms($opt);
    }

    public function __construct(array $opt = []) {
        $srv = isset($opt['srv']) ? $opt['srv'] : SMS_SRV;
        $usr = isset($opt['usr']) ? $opt['usr'] : SMS_TOKEN_KEY;
        $tok = isset($opt['tok']) ? $opt['tok'] : SMS_TOKEN_SECRET;

        $this->_link = new \BrSms($srv, $usr, $tok);
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

