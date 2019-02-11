<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-2-9 16:01:06
 * Last: 2019-2-11 15:09:02
 */
declare(strict_types = 1);

namespace lib;

use lib\Dns\IDns;

class Dns {

    // --- 解析类型 ---
    const TYPE_A = 'A';
    const TYPE_NS = 'NS';
    const TYPE_MX = 'MX';
    const TYPE_TXT = 'TXT';
    const TYPE_CNAME = 'CNAME';
    const TYPE_SRV = 'SRV';
    const TYPE_AAAA = 'AAAA';
    const TYPE_CAA = 'CAA';
    /** @var string 显性 URL 转发 */
    const TYPE_REDIRECT_URL = 'REDIRECT_URL';
    /** @var string 隐形 URL 转发 */
    const TYPE_FORWARD_URL = 'FORWARD_URL';

    // --- 解析线路 ---
    const LINE_DEFAULT = 'default';
    /** @var string 电信 */
    const LINE_TELECOM = 'telecom';
    /** @var string 联通 */
    const LINE_UNICOM = 'unicom';
    /** @var string 移动 */
    const LINE_MOBILE = 'mobile';
    /** @var string 海外 */
    const LINE_OVERSEA = 'oversea';
    /** @var string 教育网 */
    const LINE_EDU = 'edu';
    // --- 其他线路参考 https://help.aliyun.com/document_detail/29807.html ---

    /**
     * @param \lib\Aliyun $core
     * @return IDns
     */
    public static function get($core): IDns {
        // --- 这个判断暂时先没有判断，等引入别的云服务再判断 ---
        //if ($core instanceof Aliyun) {
            $class = 'lib\\Dns\\Aliyun';
            return new $class($core);
        //}
    }

}

