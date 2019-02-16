<?php
/**
 * --- 此类不能单独使用，属于 Core 性质 ---
 * --- 腾讯的类库比较蛋疼，特么的有些库还没有 3.0 的 SDK，新老 SDK 混用 ---
 * For tencentcloud-sdk-php 3.0.49
 * Url: https://github.com/TencentCloud/tencentcloud-sdk-php
 * Old: https://github.com/QcloudApi/qcloudapi-sdk-php (替换 QcloudApi 目录)
 * User: JianSuoQiYue
 * Date: 2019-2-12 00:09:50
 * Last: 2019-2-16 21:29:45
 */
declare(strict_types = 1);

namespace lib;

use TencentCloud\Common\Credential;

require ETC_PATH.'tencentcloud.php';

class TencentCloud {

    private $_link = NULL;
    private $_linkOld = NULL;

    private $_cofing = [];
    public $__orig = false;

    public static function get(array $opt = []) {
        return new self($opt);
    }

    public function __construct(array $opt = []) {
        if (isset($opt['orig'])) {
            $this->__orig = $opt['orig'];
        }
        $this->_cofing = [
            'SecretId'  => isset($opt['secretId']) ? $opt['secretId'] : TENCENTCLOUD_SECRET_ID,
            'SecretKey' => isset($opt['secretKey']) ? $opt['secretKey'] : TENCENTCLOUD_SECRET_KEY,
            'RequestMethod'  => 'GET',
            'DefaultRegion'  => isset($opt['region']) ? $opt['region'] : TENCENTCLOUD_REGION
        ];
    }

    // --- 内部方法 ---
    public function __getCore() {
        if ($this->_link === NULL) {
            require_once LIB_PATH . 'TencentCloud/tencentcloud-sdk-php/TCloudAutoLoader.php';
            $this->_link = new Credential($this->_cofing['SecretId'], $this->_cofing['SecretKey']);
        }
        return $this->_link;
    }
    public function __getOldCore() {
        if ($this->_linkOld === NULL) {
            require_once LIB_PATH . 'TencentCloud/tencentcloud-sdk-php/src/QcloudApi/QcloudApi.php';
            $this->_linkOld = $this->_cofing;
        }
        return $this->_linkOld;
    }

}

