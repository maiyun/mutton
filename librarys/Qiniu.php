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
        $encodedPutPolicy = urlsafe_base64_encode($putPolicy);
        $sign = hmac_sha1($encodedPutPolicy, QINIU_SECRETKEY);
        $encodedSign = urlsafe_base64_encode($sign);
        $uploadToken = QINIU_ACCESSKEY + ':' + $encodedSign + ':' + $encodedPutPolicy;
        return $uploadToken;
    }

}

