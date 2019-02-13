<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-2-9 22:05:30
 * Last: 2019-2-11 14:55:26
 */

declare(strict_types = 1);

namespace lib\Dns;

interface IDns {

    public function __construct($core);

    /**
     * --- 添加域名到 DNS 解析 ---
     * @param string $domain 如 maiyun.net
     * @param array $opt
     * @return mixed
     */
    public function addDomain(string $domain, array $opt = []);

    /**
     * --- 删除域名 ---
     * @param string $domain
     * @return mixed
     */
    public function deleteDomain(string $domain);

    /**
     * --- 获取域名解析列表 ---
     * @param array $opt
     * @return mixed
     */
    public function describeDomains(array $opt = []);

    /**
     * --- 获取解析记录列表 ---
     * @param string $domain
     * @param array $opt
     * @return mixed
     */
    public function describeDomainRecords(string $domain, array $opt = []);

    /**
     * --- 添加解析记录 ---
     * @param string $domain
     * @param string $rr 解析记录头，如 @，www
     * @param string $type 解析记录类型，如 Dns::TYPE_A
     * @param string $value 解析值
     * @param int $ttl
     * @param int $priority
     * @param string $line
     * @return mixed
     */
    public function addDomainRecord(string $domain, string $rr, string $type, string $value, int $ttl = 600, int $priority = 10, string $line = 'default');

    /**
     * --- 删除解析记录 ---
     * @param string $recordId
     * @param string $domain
     * @return mixed
     */
    public function deleteDomainRecord(string $recordId, string $domain = '');

    /**
     * --- 修改解析记录 ---
     * @param string $recordId
     * @param string $rr
     * @param string $type
     * @param string $value
     * @param string $domain
     * @param int $ttl
     * @param int $priority
     * @param string $line
     * @return mixed
     */
    public function updateDomainRecord(string $recordId, string $rr, string $type, string $value, string $domain = '', int $ttl = 600, int $priority = 10, string $line = 'default');

    /**
     * --- 根据 rr 值删除对应解析 ---
     * @param string $domain
     * @param string $rr 如 @、www
     * @return mixed
     */
    public function deleteSubDomainRecords(string $domain, string $rr);

    /**
     * --- 设置记录解析状态，启用或禁用 ---
     * @param string $recordId Ali: recordId, Tencent: domain
     * @param bool $status
     * @return mixed
     */
    public function setDomainRecordStatus(string $recordId, bool $status);

}

