<?php
/**
 * User: JianSuoQiYue
 * Date: 2019-2-12 15:43:30
 * Last: 2019-2-16 21:29:38
 */
declare(strict_types = 1);

namespace lib\Dns;

class TencentCloud implements IDns {

    /** @var \lib\TencentCloud */
    private $_link = null;
    private $_service = null;

    public function __construct($core) {
        $this->_link = $core;
        $config = $this->_link->__getOldCore();
        $this->_service = \QcloudApi::load(\QcloudApi::MODULE_CNS, $config);
    }

    /**
     * @param string $domain
     * @param array $opt
     * @return mixed
     */
    public function addDomain(string $domain, array $opt = []) {
        if ($this->_link->__orig) {
            $opt['domain'] = $domain;
            return $this->_service->DomainCreate($opt);
        } else {
            $pkg = [
                'domain' => $domain
            ];
            $rsp = $this->_service->DomainCreate($pkg);
            if (isset($rsp['data'])) {
                $rtn = [
                    'PunyCode' => $rsp['data']['punycode'],
                    'RequestId' => '',
	                'DomainName' => $rsp['data']['domain'],
	                'DomainId' => $rsp['data']['id'],
                    'DnsServers' => [
                        'DnsServer' => []
	                ]
                ];
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] addDomain.');
            }
        }
    }

    public function deleteDomain(string $domain) {
        $pkg = [
            'domain' => $domain
        ];
        if ($this->_link->__orig) {
            return $this->_service->DomainDelete($pkg);
        } else {
            $rsp = $this->_service->DomainDelete($pkg);
            if ($rsp['code'] == 0) {
                $rtn = [
                    'RequestId' => '',
                    'DomainName' => $domain
                ];
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] deleteDomain: '.$rsp['message'].'.');
            }
        }
    }

    /**
     * @param array $opt
     * @return mixed
     */
    public function describeDomains(array $opt = []) {
        if ($this->_link->__orig) {
            return $this->_service->DomainList($opt);
        } else {
            $pkg = [];
            if (isset($opt['count'])) {
                $pkg['length'] = $opt['count'];
            }
            if (isset($opt['page'])) {
                $pkg['offset'] = ($opt['page'] - 1) * $opt['count'];
            }
            $rsp = $this->_service->DomainList($pkg);
            if (isset($rsp['data'])) {
                $rtn = [
                    'PageNumber' => 1,
                    'TotalCount' => $rsp['data']['info']['domain_total'],
                    'PageSize' => isset($pkg['length']) ? $pkg['length'] : 20,
                    'RequestId' => '',
                    'Domains' => [
                        'Domain' => []
                    ]
                ];
                foreach ($rsp['data']['domains'] as $item) {
                    $rtn['Domains']['Domain'][] = [
                        'RecordCount' => $item['records'],
                        'PunyCode' => $item['punycode'],
                        'VersionCode' => '',
                        'AliDomain' => false,
                        'DomainName' => $item['name'],
                        'DomainId' => $item['id'],
                        'DnsServers' => [
                            'DnsServer' => []
                        ],
                        'VersionName' => ''
                    ];
                }
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] describeDomains: '.$rsp['message'].'.');
            }
        }
    }

    /**
     * @param string $domain
     * @param array $opt
     * @return mixed
     */
    public function describeDomainRecords(string $domain, array $opt = []) {
        if ($this->_link->__orig) {
            $opt['domain'] = $domain;
            return $this->_service->RecordList($opt);
        } else {
            $pkg = [
                'domain' => $domain
            ];
            if (isset($opt['count'])) {
                $pkg['length'] = $opt['count'];
            }
            if (isset($opt['page'])) {
                $pkg['offset'] = ($opt['page'] - 1) * $opt['count'];
            }
            $rsp = $this->_service->RecordList($pkg);
            if (isset($rsp['data'])) {
                $rtn = [
                    'PageNumber' => 1,
                    'TotalCount' => $rsp['data']['info']['record_total'],
	                'PageSize' => isset($pkg['length']) ? $pkg['length'] : 20,
	                'RequestId' => '',
	                'DomainRecords' => [
                        'Record' => []
                    ]
                ];
                foreach ($rsp['data']['records'] as $item) {
                    $rtn['DomainRecords']['Record'][] = [
                        'RR' => $item['name'],
                        'Status' => strtoupper($item['status']),
                        'Value' => $item['value'],
                        'Weight' => 1,
                        'RecordId' => $item['id'],
                        'Type' => $item['type'],
                        'DomainName' => $rsp['data']['domain']['name'],
                        'Locked' => $item['enabled'] == 1 ? false : true,
                        'Line' => $item['line'] == '默认' ? 'default' : $item['line'],
                        'TTL' => $item['ttl'],
                        'Priority' => $item['mx']
                    ];
                }
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] describeDomainRecords.');
            }
        }
    }

    /**
     * --- 添加解析记录 ---
     * @param string $domain
     * @param string $rr 解析记录头，如 @，www
     * @param string $type 解析记录类型，如 Dns::TYPE_A
     * @param string $value 解析值
     * @param int $ttl
     * @param int $priority mx
     * @param string $line
     * @return mixed
     */
    public function addDomainRecord(string $domain, string $rr, string $type, string $value, int $ttl = 600, int $priority = 10, string $line = '默认') {
        $pkg = [
            'domain' => $domain,
            'subDomain' => $rr,
            'recordType' => $type,
            'recordLine' => $line,
            'value' => $value,
            'ttl' => $ttl,
            'mx' => $priority
        ];
        if ($this->_link->__orig) {
            return $this->_service->RecordCreate($pkg);
        } else {
            $rsp = $this->_service->RecordCreate($pkg);
            if (isset($rsp['data'])) {
                $rtn = [
                    'RequestId' => '',
                    'RecordId' => $rsp['data']['record']['id']
                ];
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] addDomainRecord.');
            }
        }
    }

    public function deleteDomainRecord(string $recordId, string $domain = '') {
        $pkg = [
            'domain' => $domain,
            'recordId' => $recordId
        ];
        if ($this->_link->__orig) {
            return $this->_service->RecordDelete($pkg);
        } else {
            $rsp = $this->_service->RecordDelete($pkg);
            if ($rsp['code'] == 0) {
                $rtn = [
                    'RequestId' => '',
                    'RecordId' => $recordId
                ];
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] deleteDomainRecord: '.$rsp['message'].'.');
            }
        }
    }

    public function updateDomainRecord(string $recordId, string $rr, string $type, string $value, string $domain = '', int $ttl = 600, int $priority = 10, string $line = 'default') {
        $pkg = [
            'domain' => $domain,
            'recordId' => $recordId,
            'subDomain' => $rr,
            'recordType' => $type,
            'recordLine' => $line,
            'value' => $value,
            'ttl' => $ttl,
            'mx' => $priority
        ];
        if ($this->_link->__orig) {
            return $this->_service->RecordModify($pkg);
        } else {
            $rsp = $this->_service->RecordModify($pkg);
            if (isset($rsp['data'])) {
                $rtn = [
                    'RequestId' => '',
                    'RecordId' => $rsp['data']['record']['id']
                ];
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] updateDomainRecord.');
            }
        }
    }

    public function deleteSubDomainRecords(string $domain, string $rr) {
    }

    public function setDomainRecordStatus(string $recordId, bool $status) {
        $status = $status ? 'enable' : 'disable';
        $pkg = [
            'domain' => $recordId,
            'status' => $status ? 'enable' : 'disable'
        ];
        if ($this->_link->__orig) {
            return $this->_service->SetDomainStatus($pkg);
        } else {
            $rsp = $this->_service->SetDomainStatus($pkg);
            if ($rsp['code'] == 0) {
                $rtn = [
                    'RequestId' => '',
                    'RecordId' => '',
                    'Status' => strtoupper($status[0]) . substr($status, 1)
                ];
                return json_decode(json_encode($rtn));
            } else {
                throw new \Error('[Error][Dns][TC] setDomainRecordStatus: '.$rsp['message'].'.');
            }
        }
    }

}

