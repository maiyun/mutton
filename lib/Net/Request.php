<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2020-3-5 09:35:23
 * Last: 2020-3-5 10:34:23
 */
declare(strict_types = 1);

namespace lib\Net;

use lib\Net;

class Request {

    /** @var array|null get 或 post 的数据 */
    private $_data = null;
    /** @var string 访问的 URL */
    private $_url = '';
    /** @var array 要传递的参数 */
    private $_opt = [];

    public function __construct(string $url) {
        $this->_url = $url;
    }

    /**
     * --- 设置 get 或 post 的数据 ---
     * @param array $data
     * @return $this
     */
    public function data(array $data) {
        $this->_data = $data;
        return $this;
    }

    /**
     * --- 设置 get 或 post 请求 ---
     * @param string $method
     * @return $this
     */
    public function method(string $method) {
        $this->_opt['method'] = $method;
        return $this;
    }

    /**
     * --- method get 方法别名 ---
     * @return $this
     */
    public function get() {
        return $this->method('GET');
    }

    /**
     * --- method post 方法别名 ---
     * @return $this
     */
    public function post() {
        return $this->method('POST');
    }

    /**
     * --- 设置提交模式，json 还是普通 form ---
     * @param string $type
     * @return $this
     */
    public function type(string $type) {
        $this->_opt['type'] = $type;
        return $this;
    }

    /**
     * --- type json 方法别名 ---
     * @return $this
     */
    public function json() {
        return $this->type('json');
    }

    /**
     * --- 设置请求有效期 ---
     * @param int $timeout
     * @return $this
     */
    public function timeout(int $timeout) {
        $this->_opt['timeout'] = $timeout;
        return $this;
    }

    /**
     * --- 设置是否跟随请求方的 location，留空为跟随，不设置为不跟随 ---
     * @param int $follow
     * @return $this
     */
    public function follow(int $follow = 5) {
        $this->_opt['follow'] = $follow;
        return $this;
    }

    /**
     * --- 设置域名 -> ip的对应键值，就像电脑里的 hosts 一样 ---
     * @param array $hosts
     * @return $this
     */
    public function hosts(array $hosts) {
        $this->_opt['hosts'] = $hosts;
        return $this;
    }

    /**
     * --- 设置后将直接保存到本地文件，不会返回，save 为本地实体路径 ---
     * @param string $save
     * @return $this
     */
    public function save(string $save) {
        $this->_opt['save'] = $save;
        return $this;
    }

    /**
     * --- 设置使用的本地网卡 IP ---
     * @param string $addr
     * @return $this
     */
    public function local(string $addr) {
        $this->_opt['local'] = $addr;
        return $this;
    }

    /**
     * --- 是否连接复用，为空则为复用，默认不复用，复用后需要手动关闭连接 ---
     * @param bool $reuse
     * @return $this
     */
    public function reuse(bool $reuse = true) {
        $this->_opt['reuse'] = $reuse;
        return $this;
    }

    /**
     * --- 批量设置提交的 headers ---
     * @param array $headers
     * @return $this
     */
    public function headers(array $headers) {
        $this->_opt['headers'] = $headers;
        return $this;
    }

    /**
     * --- 设置单条 header ---
     * @param string $name
     * @param string $val
     * @return $this
     */
    public function setHeader(string $name, string $val) {
        if (!isset($this->_opt['headers'])) {
            $this->_opt['headers'] = [];
        }
        $this->_opt['headers'][$name] = $val;
        return $this;
    }

    /**
     * --- 发起请求 ---
     * @param array|null $cookie
     * @return Response
     */
    public function request(?array &$cookie = null) {
        return Net::request($this->_url, $this->_data, $this->_opt, $cookie);
    }

}

