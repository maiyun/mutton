<?php
/**
 * 小程序、微信的登录、公众号/小程序/扫码支付均有，均OK
 * For 3.0.9
 * Url: https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=11_1
 * User: JianSuoQiYue
 * Date: 2015/5/7 13:50
 * Last: 2018-7-28 15:45:39
 */
declare(strict_types = 1);

namespace lib;

require ETC_PATH.'wechat.php';

class Wechat {

    /* --- 公众号相关 --- */

    /**
     * --- 公众号登录 ---
     * @param string $url 登录跳转回的 URL
     * @param null|string $appid
     */
    public static function login(string $url, ?string $appid = NULL): void {

        $appid = $appid ? $appid : WECHAT_APPID;
        $lenUrl = substr($url, 0, 6);
        if ($lenUrl != 'https:' && $lenUrl != 'http:/') {
            $url = HTTP_PATH . $url;
        }
        header('Location: //open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri=' . urlencode($url) . '&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect');

    }

    /**
     * --- 登录回跳处理 ---
     * @param null|string $appid
     * @param null|string $secret
     * @return bool|object
     * @throws \Exception
     */
    public static function redirect(?string $appid = NULL, ?string $secret = NULL) {
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

    /**
     * --- 获取用户信息 ---
     * @param string $access_token 用户的 access_token
     * @param string $openid 用户的 openid
     * @return object
     * @throws \Exception
     */
    public static function getUserInfo(string $access_token, string $openid): object {
        return json_decode(Net::get('https://api.weixin.qq.com/sns/userinfo?access_token='.$access_token.'&openid='.$openid));
    }

    // --- 获取服务器 Signature (7200秒刷新一次，不要频繁获取，建议留 1分钟 7140 重新获取) ---
    // --- ['onMenuShareTimeline', 'onMenuShareAppMessage'] ---
    /**
     * @param array $apiList
     * @param string $accessToken
     * @param string $ticket
     * @param null|string $appid
     * @param null|string $secret
     * @return array
     * @throws \Exception
     */
    public static function getJSConfig(array $apiList, string $accessToken, string $ticket, ?string $appid = NULL, ?string $secret = NULL): array {
        $appid = $appid ? $appid : WECHAT_APPID;
        $secret = $secret ? $secret : WECHAT_SECRET;

        $a = false;
        if ($accessToken == '') {
            $accessToken = self::getAccessToken($appid, $secret);
            $a = true;
        }

        $b = false;
        if ($ticket == '') {
            $ticket = self::getTicket($accessToken);
            $b = true;
        }

        $noncestr = Text::random(16);
        $time = $_SERVER['REQUEST_TIME'];
        $string = 'jsapi_ticket='.$ticket.'&noncestr='.$noncestr.'&timestamp='.$time.'&url=http' . ((HTTPS ? 's' : '') . '://') . HTTP_HOST . $_SERVER['REQUEST_URI'];
        return [
            'accessToken' => $a ? $accessToken : '',
            'ticket' => $b ? $ticket : '',
            'js' => 'wx.config({debug:false,appId:"'.$appid.'",timestamp:"'.$time.'",nonceStr:"'.$noncestr.'",signature:"'.sha1($string).'",jsApiList:'.json_encode($apiList).'});'
        ];
    }

    /**
     * 获取微信的 Ticket，7200 有效期，不要频繁获取，建议留 1分钟 7140 重新获取 ---
     * @param string $accessToken
     * @return string
     * @throws \Exception
     */
    public static function getTicket(string $accessToken): string {
        $q = Net::get('https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $accessToken . '&type=jsapi');
        $q = json_decode($q);
        return $q->ticket;
    }

    /* --- 小程序 --- */

    /**
     * --- 小程序登录 ---
     * @param string $code
     * @param null|string $appid
     * @param null|string $secret
     * @return object
     * @throws \Exception
     */
    public static function loginMS(string $code, ?string $appid = NULL, ?string $secret = NULL): object {
        $appid = $appid ? $appid : WECHAT_APPID;
        $secret = $secret ? $secret : WECHAT_SECRET;

        $r = Net::get('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code');
        $j = json_decode($r);
        return $j;
    }


    /* --- 公共 --- */

    /**
     * 获取微信 Access Token，7200 有效期，不要频繁获取，建议留 1分钟 7140 重新获取 ---
     * @param null|string $appid
     * @param null|string $secret
     * @return string
     * @throws \Exception
     */
    public static function getAccessToken(?string $appid = NULL, ?string $secret = NULL): string {
        $appid = $appid ? $appid : WECHAT_APPID;
        $secret = $secret ? $secret : WECHAT_SECRET;

        $r = Net::get('https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret);
        $r = json_decode($r);
        return $r->access_token;
    }

    // --- 微信支付相关 ---

    /**
     * --- 创建支付 ---
     * @param array $opt
     * @param array $config
     * @return mixed
     * @throws \WxPayException
     */
    /*
     * $wxJsApi = Wechat::createPay([
            'body' => 'Online Pay',
            'attach' => '',
            'out_trade_no' => $orderNumber,
            'total_fee' => $orderPrice,
            'notify_url' => HTTP_PATH . 'api/wxpayNotify',
            'type' => 'NATIVE',
            'openid' => $logUser->wx_openid
        ]);
     */
    public static function createPay(array $opt = [], array $config = []) {

        require LIB_PATH . 'Wechat/WxpayAPI/lib/WxPay.Api.php';

        if (!isset($opt['type'])) {
            $opt['type'] = 'JSAPI';
        }

        $input = new \WxPayUnifiedOrder();
        $input->SetBody($opt['body']);
        $input->SetAttach($opt['attach']);
        $input->SetOut_trade_no($opt['out_trade_no']);
        $input->SetTotal_fee($opt['total_fee'] * 100);
        $input->SetTime_start(date("YmdHis"));
        // --- 15 分钟内（预留30秒）支付完毕 ---
        $input->SetTime_expire(date("YmdHis", $_SERVER['REQUEST_TIME'] + 900 + 30));
        $input->SetGoods_tag('order');
        $input->SetNotify_url($opt['notify_url']);
        if ($opt['type'] == 'JSAPI') {
            require LIB_PATH . 'Wechat/WxpayAPI/lib/WxPay.JsApiPay.php';
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid($opt['openid']);
        } else {
            require LIB_PATH . 'Wechat/WxpayAPI/lib/WxPay.NativePay.php';
            $input->SetTrade_type("NATIVE");
        }
        $wpconfig = new \WxPayConfig();
        if (isset($config['appid'])) {
            $wpconfig->SetAppId($config['appid']);
        }
        if (isset($config['mchid'])) {
            $wpconfig->SetAppId($config['mchid']);
        }
        if (isset($config['key'])) {
            $wpconfig->SetAppId($config['key']);
        }
        if (isset($config['secret'])) {
            $wpconfig->SetAppId($config['secret']);
        }
        $wxOrder = \WxPayApi::unifiedOrder($wpconfig, $input);

        if ($opt['type'] == 'JSAPI') {
            $tools = new \JsApiPay();
            $jsApiParameters = $tools->GetJsApiParameters($wxOrder);
            // --- 要 decode 一下否则是个字符串而不是 json 对象就呵呵哒了 ---
            return json_decode($jsApiParameters, true);
        } else {
            $notify = new \NativePay();
            $result = $notify->GetPayUrl($input);
            return $result["code_url"];
        }

    }

    // --- 支付回调 ---

    /**
     * @param callable|NULL $callback
     * @param array $config
     */
    public static function payCallback(?callable $callback = NULL, array $config = []): void {

        require LIB_PATH . 'Wechat/WxpayAPI/lib/WxPay.Api.php';
        require LIB_PATH . 'Wechat/WxpayAPI/lib/WxPay.Notify.php';
        require LIB_PATH . 'Wechat/WxpayAPI/lib/WxPay.NotifyCallBack.php';
        require LIB_PATH . 'Wechat/WxpayAPI/lib/WxPay.Config.php';

        $wpconfig = new \WxPayConfig();
        if (isset($config['appid'])) {
            $wpconfig->SetAppId($config['appid']);
        }
        if (isset($config['mchid'])) {
            $wpconfig->SetAppId($config['mchid']);
        }
        if (isset($config['key'])) {
            $wpconfig->SetAppId($config['key']);
        }
        if (isset($config['secret'])) {
            $wpconfig->SetAppId($config['secret']);
        }

        $notify = new \WxPayNotifyCallBack();
        if ($callback !== NULL) {
            $notify->setCallback($callback);
        }
        $notify->Handle($wpconfig, false);

    }

}

