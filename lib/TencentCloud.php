<?php
/**
 * --- 此类不能单独使用，属于 Core 性质 ---
 * --- 腾讯的类库比较蛋疼，特么的有些库还没有 3.0 的 SDK，新老 SDK 混用 ---
 * For tencentcloud-sdk-php 3.0.49
 * Url: https://github.com/TencentCloud/tencentcloud-sdk-php
 * Old: https://github.com/QcloudApi/qcloudapi-sdk-php (替换 QcloudApi 目录)
 * User: JianSuoQiYue
 * Date: 2019-2-12 00:09:50
 * Last: 2019-2-12 14:17:15
 */
declare(strict_types = 1);

namespace lib;

use TencentCloud\Common\Credential;

require ETC_PATH.'tencentcloud.php';

class TencentCloud {

    public $__link = NULL;

    public static function get(array $opt = []) {
        return new self($opt);
    }

    public function __construct(array $opt = []) {
        $v = 3;
        if (isset($opt['v'])) {
            $v = $opt['v'];
        }
        $orig = false;
        if (isset($opt['orig'])) {
            $orig = $opt['orig'];
        }
        if ($v >= 3) {
            // --- 新版 SDK ---
            require_once LIB_PATH . 'TencentCloud/tencentcloud-sdk-php/TCloudAutoLoader.php';
            $cred = new Credential(isset($opt['secretId']) ? $opt['secretId'] : TENCENTCLOUD_SECRET_ID, isset($opt['secretKey']) ? $opt['secretKey'] : TENCENTCLOUD_SECRET_KEY);
            $this->__link = [
                'cred' => $cred,
                'orig' => $orig
            ];
        } else {
            // --- 老版 SDK ---
            require_once LIB_PATH . 'TencentCloud/tencentcloud-sdk-php/src/QcloudApi/QcloudApi.php';
            $config = [
                'SecretId'  => isset($opt['secretId']) ? $opt['secretId'] : TENCENTCLOUD_SECRET_ID,
                'SecretKey' => isset($opt['secretKey']) ? $opt['secretKey'] : TENCENTCLOUD_SECRET_KEY,
                'RequestMethod'  => 'GET',
                'DefaultRegion'  => isset($opt['region']) ? $opt['region'] : TENCENTCLOUD_REGION
            ];
            $this->__link = [
                'config' => $config,
                'orig' => $orig
            ];
        }
    }

}

