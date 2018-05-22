<?php

/* For Qingmu Tongxun */

namespace C\lib {

    require LIB_PATH.'qmsms-sdk/qmsms.php';

    class Sms {

        private $_qmsms = false;

        public function __construct($srv = NULL, $usr = NULL, $tok = NULL) {
            $srv = $srv === NULL ? SMS_SRV : $srv;
            $usr = $usr === NULL ? SMS_USER : $usr;
            $tok = $tok === NULL ? SMS_TOKEN : $tok;

            $this->_qmsms = new \QmSms($srv, $usr, $tok);
        }

        public function send($phone, $body, $sign = '0', $template = '0') {
            return $this->_qmsms->send($phone, $body, $sign, $template);
        }

        public function sendMarket($phone, $sign, $template) {
            return $this->_qmsms->sendMarket($phone, $sign, $template);
        }

        public function task($name, $phone, $sign, $template) {
            return $this->_qmsms->task($name, $phone, $sign, $template);
        }

    }

}

