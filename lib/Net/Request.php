<?php
/**
 * User: JianSuoQiYue
 * Date: 2018-12-10 19:17:28
 * Last: 2018-12-10 19:17:31
 */
declare(strict_types = 1);

namespace lib\Net;

class Request {

    private $_method = '';
    private $_type = '';
    private $_timeout = 0;
    private $_userAgent = '';
    private $_httpHeader = NULL;
    private $_referer = '';
    private $_curlOpt = NULL;

    public function __construct(array $opt = []) {
        $this->set($opt);
    }

    public static function get(array $opt = []): Request {
        return new Request($opt);
    }

    // --- 批量设置 ---
    public function set(array $opt): void {
        $this->_method = isset($opt['method']) ? strtoupper($opt['method']) : 'GET';
        $this->_type = isset($opt['type']) ? strtolower($opt['type']) : 'form';
        $this->_timeout = isset($opt['timeout']) ? (int)$opt['timeout'] : 10;
        $this->_userAgent = isset($opt['userAgent']) ? $opt['userAgent'] : 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.67 Safari/537.36';
        $this->_httpHeader = isset($opt['httpHeader']) && is_array($opt['httpHeader']) ? $opt['httpHeader'] : NULL;
        $this->_curlOpt = isset($opt['curlOpt']) && is_array($opt['curlOpt']) ? $opt['httpHeader'] : NULL;
    }

    public function setMethod(string $method): Request {
        $this->_method = $method;
        return $this;
    }
    public function getMethod(): string {
        return $this->_method;
    }

    public function setType(string $type): Request {
        $this->_type = strtolower($type);
        return $this;
    }
    public function getType(): string {
        return $this->_type;
    }

    public function setTimeout(int $timeout): Request {
        $this->_timeout = $timeout;
        return $this;
    }
    public function getTimeout(): int {
        return $this->_timeout;
    }

    public function setUserAgent(string $ua): Request {
        $this->_userAgent = $ua;
        return $this;
    }
    public function getUserAgent(): string {
        return $this->_userAgent;
    }

    public function setHttpHeader(?array $httpHeader): Request {
        $this->_httpHeader = $httpHeader;
        return $this;
    }
    public function getHttpHeader(): ?array {
        return $this->_httpHeader;
    }

    public function setReferer(string $url): Request {
        $this->_referer = $url;
        return $this;
    }
    public function getReferer(): string {
        return $this->_referer;
    }

    public function setCurlOpt(?array $list): Request {
        $this->_curlOpt = $list;
        return $this;
    }
    public function getCurlOpt(): ?array {
        return $this->_curlOpt;
    }

}

