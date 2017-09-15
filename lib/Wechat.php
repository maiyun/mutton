<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/5/7
 * Time: 13:50
 */

namespace C\lib {

    class Wechat {

        public static function login($url, $appid = NULL) {

            $appid = $appid ? $appid : WECHAT_APPID;
            header('Location: //open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=' . urlencode($url) . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect');

        }

        public static function redirect($appid = NULL, $secret = NULL) {

            $appid = $appid ? $appid : WECHAT_APPID;
            $secret = $secret ? $secret : WECHAT_SECRET;
            if(isset($_GET['code']) && $_GET['code'] != '') {
                if ($r = Net::get('https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $appid . '&secret=' . $secret . '&code=' . $_GET['code'] . '&grant_type=authorization_code')) {
                    $j = json_decode($r);
                    if (!isset($j->errcode)) {
                        return $j;
                    } else {
                        return false;
                        //$this->writeJson(-2, 'errcode: ' . $j->errcode . ', errmsg: ' . $j->errmsg . '.');
                    }
                } else {
                    return false;
                    //$this->writeJson(-1, 'Network is wrong.');
                }
            } else {
                return false;
            }

        }

    }

}

