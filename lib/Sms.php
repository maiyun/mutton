<?php

/* For Qingmu Tongxun */

namespace C\lib {

    class Sms {

        public static function send($msg, $mb, $user = NULL, $pwd = NULL, $serv = NULL) {

            $user = $user ? $user : SMS_USER;
            $pwd = $pwd ? $pwd : SMS_PWD;
            $serv = $serv ? $serv : SMS_SERV;
            if(is_array($mb)) $mb = implode(',', $mb);

            $time = date('YmdHis');
            $data = [
                'uid' => $user,
                'pw' => md5($pwd . $time),
                'mb' => $mb,
                'ms' => $msg,
                'tm' => $time
            ];
            $r = Net::post('http://'.$serv.':18002/send.do', $data);
            $ra = explode(',', $r);
            if($ra[0] == '0') {
                return true;
            } else {
                return false;
            }

        }

    }

}