<?php
/**
 * For aliyun-php-sdk-alidns V20150109
 * Url: https://github.com/aliyun/aliyun-openapi-php-sdk/tree/master/aliyun-php-sdk-alidns/Alidns/Request
 * User: JianSuoQiYue
 * Date: 2019-2-9 18:19:20
 * Last: 2019-2-9 22:54:30
 */
declare(strict_types = 1);

namespace lib\Dns;

spl_autoload_register(function (string $name) {
    if (substr($name, 0, 6) === 'Alidns') {
        require LIB_PATH  . 'Dns/aliyun-php-sdk-alidns/' . str_replace('\\', '/', $name) . '.php';
    }
}, true);

use Alidns\Request\V20150109\AddDomainRecordRequest;
use Alidns\Request\V20150109\AddDomainRequest;
use Alidns\Request\V20150109\DeleteDomainRecordRequest;
use Alidns\Request\V20150109\DeleteDomainRequest;
use Alidns\Request\V20150109\DeleteSubDomainRecordsRequest;
use Alidns\Request\V20150109\DescribeDomainRecordsRequest;
use Alidns\Request\V20150109\DescribeDomainsRequest;
use Alidns\Request\V20150109\SetDomainRecordStatusRequest;
use Alidns\Request\V20150109\UpdateDomainRecordRequest;

class Aliyun implements IDns {

    /** @var \DefaultAcsClient */
    private $_link = null;

    public function __construct($core) {
        $this->_link = $core->__link;
    }

    /**
     * --- 添加域名到 DNS 解析 ---
     * @param string $domain 如 maiyun.net
     * @param array $opt
     * @return mixed
     * @throws \ClientException
     * @throws \ServerException
     */
    public function addDomain(string $domain, array $opt = []) {
        $adr = new AddDomainRequest();
        $adr->setDomainName($domain);
        if (isset($opt['group'])) {
            $adr->setGroupId($opt['group']);
        }
        return $this->_link->getAcsResponse($adr);
    }

    /**
     * --- 删除域名 ---
     * @param string $domain
     * @return mixed
     * @throws \ClientException
     * @throws \ServerException
     */
    public function deleteDomain(string $domain) {
        $ddr = new DeleteDomainRequest();
        $ddr->setDomainName($domain);
        return $this->_link->getAcsResponse($ddr);
    }

    /**
     * --- 获取域名解析列表 ---
     * @param array $opt
     * @return mixed
     * @throws \ClientException
     * @throws \ServerException
     */
    public function describeDomains(array $opt = []) {
        $ddr = new DescribeDomainsRequest();
        if (isset($opt['count'])) {
            $ddr->setPageSize($opt['count']);
        }
        if (isset($opt['page'])) {
            $ddr->setPageNumber($opt['page']);
        }
        return $this->_link->getAcsResponse($ddr);
    }

    /**
     * --- 获取解析记录列表 ---
     * @param string $domain
     * @param array $opt
     * @return mixed
     * @throws \ClientException
     * @throws \ServerException
     */
    public function describeDomainRecords(string $domain, array $opt = []) {
        $ddrr = new DescribeDomainRecordsRequest();
        $ddrr->setDomainName($domain);
        if (isset($opt['count'])) {
            $ddrr->setPageSize($opt['count']);
        }
        if (isset($opt['page'])) {
            $ddrr->setPageNumber($opt['page']);
        }
        return $this->_link->getAcsResponse($ddrr);
    }

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
     * @throws \ClientException
     * @throws \ServerException
     */
    public function addDomainRecord(string $domain, string $rr, string $type, string $value, int $ttl = 600, int $priority = 10, string $line = 'default') {
        $adr = new AddDomainRecordRequest();
        $adr->setDomainName($domain);
        $adr->setRR($rr);
        $adr->setType($type);
        $adr->setValue($value);
        $adr->setTTL($ttl);
        $adr->setPriority($priority);
        $adr->setLine($line);
        return $this->_link->getAcsResponse($adr);
    }

    /**
     * --- 删除解析记录 ---
     * @param string $recordId
     * @param string$domain
     * @return mixed
     * @throws \ClientException
     * @throws \ServerException
     */
    public function deleteDomainRecord(string $recordId, string $domain = '') {
        $ddrr = new DeleteDomainRecordRequest();
        $ddrr->setRecordId($recordId);
        return $this->_link->getAcsResponse($ddrr);
    }

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
     * @throws \ClientException
     * @throws \ServerException
     */
    public function updateDomainRecord(string $recordId, string $rr, string $type, string $value, string $domain = '', int $ttl = 600, int $priority = 10, string $line = 'default') {
        $udr = new UpdateDomainRecordRequest();
        $udr->setRecordId($recordId);
        $udr->setRR($rr);
        $udr->setType($type);
        $udr->setValue($value);
        $udr->setTTL($ttl);
        $udr->setPriority($priority);
        $udr->setLine($line);
        return $this->_link->getAcsResponse($udr);
    }

    /**
     * --- 根据 rr 值删除对应解析 ---
     * @param string $domain
     * @param string $rr 如 @、www
     * @return mixed
     * @throws \ClientException
     * @throws \ServerException
     */
    public function deleteSubDomainRecords(string $domain, string $rr) {
        $dsdr = new DeleteSubDomainRecordsRequest();
        $dsdr->setDomainName($domain);
        $dsdr->setRR($rr);
        return $this->_link->getAcsResponse($dsdr);
    }

    /**
     * --- 设置记录解析状态，启用或禁用 ---
     * @param string $recordId
     * @param bool $status
     * @return mixed
     * @throws \ClientException
     * @throws \ServerException
     */
    public function setDomainRecordStatus(string $recordId, bool $status) {
        $sdrs = new SetDomainRecordStatusRequest();
        $sdrs->setRecordId($recordId);
        $sdrs->setStatus($status ? 'Enable' : 'Disable');
        return $this->_link->getAcsResponse($sdrs);
    }

}

