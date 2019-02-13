<?php
/**
 * --- 此类不能单独使用，属于 Core 性质 ---
 * For aliyun-openapi-php-sdk - aliyun-php-sdk-core 1.3.7
 * Url: https://github.com/aliyun/aliyun-openapi-php-sdk
 * User: JianSuoQiYue
 * Date: 2019-2-9 14:14:22
 * Last: 2019-2-12 00:08:26
 */
declare(strict_types = 1);

namespace lib;

require LIB_PATH.'Aliyun/aliyun-php-sdk-core/Config.php';

require ETC_PATH.'aliyun.php';

class Aliyun {

    /** @var \DefaultAcsClient */
    public $__link = NULL;

    public static function get(array $opt = []): Aliyun {
        return new self($opt);
    }

    public function __construct(array $opt = []) {
        $aki = isset($opt['accessKeyId']) ? $opt['accessKeyId'] : ALIYUN_ACCESS_KEY_ID;
        $aks = isset($opt['accessKeySecret']) ? $opt['accessKeySecret'] : ALIYUN_ACCESS_KEY_SECRET;
        $reg = isset($opt['region']) ? $opt['region'] : ALIYUN_REGION;

        $dp = \DefaultProfile::getProfile($reg, $aki, $aks);
        //\EndpointProvider::setEndpoints(['cn-hangzhou']);
        \DefaultProfile::addEndpoint('cn-hangzhou', 'cn-hangzhou', 'Alidns', 'alidns.aliyuncs.com');
        $this->__link = new \DefaultAcsClient($dp);
    }

}

