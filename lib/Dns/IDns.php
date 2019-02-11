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

    public function addDomain(string $domain, array $opt = []);
    public function deleteDomain(string $domain);
    public function describeDomains(array $opt = []);
    public function describeDomainRecords(string $domain, array $opt = []);
    public function addDomainRecord(string $domain, string $rr, string $type, string $value, int $ttl = 600, int $priority = 10, string $line = 'default');
    public function deleteDomainRecord(string $recordId);
    public function updateDomainRecord(string $recordId, string $rr, string $type, string $value, int $ttl = 600, int $priority = 10, string $line = 'default');
    public function deleteSubDomainRecords(string $domain, string $rr);
    public function setDomainRecordStatus(string $recordId, bool $status);

}

