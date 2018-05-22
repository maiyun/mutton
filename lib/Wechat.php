<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/5/7
 * Time: 13:50
 */

namespace C\lib {

    class Wechat {

        // --- 公众号登录 ---
        public static function login($url, $appid = NULL) {

            $appid = $appid ? $appid : WECHAT_APPID;
            $lenUrl = substr($url, 0, 6);
            if ($lenUrl != 'https:' && $lenUrl != 'http:/') {
                $url = 'http' . (self::isHttps() ? 's' : '') . '://' . HTTP_HOST . '/' . $url;
            }
            header('Location: //open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=' . urlencode($url) . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect');

        }

        // --- 公众号获取用户信息 ---
        public static function getUserInfo($access_token, $openid) {
            return json_decode(Net::get('https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid));
        }

        // --- 小程序登录 ---
        public static function loginMS($code, $appid = NULL, $secret = NULL) {
            $appid = $appid ? $appid : WECHAT_APPID;
            $secret = $secret ? $secret : WECHAT_SECRET;

            $r = Net::get('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code');
            $j = json_decode($r);
            return $j;
        }

        // --- 登录回跳处理 ---
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

        // --- 创建支付 ---
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

        // --- 支付回调 ---
        public static function payCallback() {

            require LIB_PATH . 'WxpayAPI/lib/WxPay.Api.php';
            require LIB_PATH . 'WxpayAPI/lib/WxPay.Notify.php';
            require LIB_PATH . 'WxpayAPI/lib/WxPay.NotifyCallBack.php';

            $notify = new \WxPayNotifyCallBack();
            $notify->Handle(false);

        }

        // --- 获取服务器 Signature (7200秒刷新一次) ---
        // --- ['onMenuShareTimeline', 'onMenuShareAppMessage'] ---
        public static function getWXConfig($apiList, $tokenTicket, $appid = NULL, $secret = NULL) {
            $appid = $appid ? $appid : WECHAT_APPID;
            $secret = $secret ? $secret : WECHAT_SECRET;

            if (!is_string($tokenTicket) || $tokenTicket == '') {
                $r = Net::get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret);
                $r = json_decode($r);
                $q = Net::get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $r->access_token . '&type=jsapi');
                $q = json_decode($q);
                $ticket = $q->ticket;
                $tokenTicket = $r->access_token . ',' . $ticket;
            } else {
                list($token, $ticket) = explode(',', $tokenTicket);
            }
            $noncestr = Text::random(16, ['L', 'U', 'N']);
            $time = $_SERVER['REQUEST_TIME'];
            $string = 'jsapi_ticket='.$ticket.'&noncestr='.$noncestr.'&timestamp='.$time.'&url=http' . ((self::isHttps() ? 's' : '') . '://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            return [$tokenTicket, 'wx.config({debug:false,appId:"'.$appid.'",timestamp:"'.$time.'",nonceStr:"'.$noncestr.'",signature:"'.sha1($string).'",jsApiList:'.json_encode($apiList).'});'];
        }

        // --- 判断是否是 HTTPS ---
        public static function isHttps() {
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

