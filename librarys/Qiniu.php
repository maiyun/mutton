<?php
/**
 * Created by PhpStorm.
 * User: Thinkpad
 * Date: 2015/8/16
 * Time: 22:33
 */

namespace Chameleon\Library;

class Qiniu {

    // --- 获取直传 token ---
    public function getToken($json = []) {

        $putPolicy = json_encode($json);
        $encodedPutPolicy = $this->base64UrlSafeEncode($putPolicy);
        $sign = hash_hmac('sha1', $encodedPutPolicy, QINIU_SECRETKEY, true);
        $encodedSign = $this->base64UrlSafeEncode($sign);
        $uploadToken = QINIU_ACCESSKEY . ':' . $encodedSign . ':' . $encodedPutPolicy;
        return $uploadToken;
        //*/
    }

    // --- url 安全 base64 ---
    public function base64UrlSafeEncode($data) {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($data));
    }

}

