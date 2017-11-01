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
            $url = 'http'.(self::isHttps() ? 's':'').'://'.HTTP_HOST.'/' . $url;
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

        public static function createPay($opt = []) {

            require LIB_PATH . 'WxpayAPI/lib/WxPay.Api.php';
            require LIB_PATH . 'WxpayAPI/lib/WxPay.JsApiPay.php';

            $input = new \WxPayUnifiedOrder();
            $input->SetBody($opt['body']);
            $input->SetAttach($opt['attach']);
            $input->SetOut_trade_no($opt['out_trade_no']);
            $input->SetTotal_fee($opt['total_fee'] * 100);
            $input->SetTime_start(date("YmdHis"));
            // --- 20 分钟内（预留30秒）支付完毕 ---
            $input->SetTime_expire(date("YmdHis", $_SERVER['REQUEST_TIME'] + 1200 + 30));
            $input->SetGoods_tag('test');
            $input->SetNotify_url($opt['notify_url']);
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($opt['openid']);
            $wxOrder = \WxPayApi::unifiedOrder($input);

            $tools = new \JsApiPay();
            $jsApiParameters = $tools->GetJsApiParameters($wxOrder);

            // --- 要 decode 一下否则是个字符串而不是 json 对象就呵呵哒了 ---
            return json_decode($jsApiParameters, true);

        }

        public static function payCallback() {

            require LIB_PATH . 'WxpayAPI/lib/WxPay.Notify.php';
            require LIB_PATH . 'WxpayAPI/lib/WxPay.NotifyCallBack.php';

            $notify = new \WxPayNotifyCallBack();
            $notify->Handle(false);

        }

        public static  function isHttps() {
            if (isset($_SERVER['HTTPS'])) {
                if ($_SERVER['HTTPS'] === 1) {  //Apache
                    return true;
                } else if ($_SERVER['HTTPS'] === 'on') { //IIS
                    return true;
                }
            } else if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === 443) { //其他
                return true;
            }
            return false;
        }

    }

}

