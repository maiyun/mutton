<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {
    "ver": "0.1",
    "folder": true,
    "url": {
        "https://curl.haxx.se/ca/cacert.pem": {
            "action": "down",
            "save": "cacert.pem"
        },
        "https://cdn.jsdelivr.net/gh/MaiyunNET/Mutton@{ver}/lib/Net/Response.php": {
            "action": "down",
            "save": "Response.php"
        }
    }
} - END
 * Date: 2015/10/26 14:23
 * Last: 2019-3-13 17:33:39, 2019-12-28 23:48:06, 2020-2-26 22:29:36
 */
declare(strict_types = 1);

namespace lib;

use CURLFile;
use lib\Net\Response;

class Net {

    /** @var array 连接池 */
    private static $_pool = [];

    /**
     * --- 获取链接 ---
     * @param string $host
     * @return resource
     */
    public static function getLink(string $host) {
        if (isset(self::$_pool[$host])) {
            return self::$_pool[$host];
        } else {
            self::$_pool[$host] = curl_init();
            return self::$_pool[$host];
        }
    }

    /**
     * --- 手段关闭复用连接 ---
     * @param string $host
     */
    public static function closeLink(string $host) {
        if (isset(self::$_pool[$host])) {
            curl_close(self::$_pool[$host]);
            unset(self::$_pool[$host]);
        }
    }

    /**
     * --- 关闭所有复用连接 ---
     */
    public static function closeAll() {
        foreach (self::$_pool as $host => $link) {
            curl_close($link);
            unset(self::$_pool[$host]);
        }
    }

    /**
     * --- 发起 GET 请求 ---
     * @param string $url
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, headers
     * @param array|null $cookie
     * @return Response
     */
    public static function get(string $url, array $opt = [], ?array &$cookie = null) {
        return self::request($url, null, $opt, $cookie);
    }

    /**
     * --- 发起 POST 请求 ---
     * @param string $url
     * @param array $data
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, reuse, headers
     * @param array|null $cookie
     * @return Response
     */
    public static function post(string $url, array $data, array $opt = [], ?array &$cookie = null) {
        $opt['method'] = 'POST';
        return self::request($url, $data, $opt, $cookie);
    }

    /**
     * --- 发起 JSON 请求 ---
     * @param string $url
     * @param array $data
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, reuse, headers
     * @param array|null $cookie
     * @return Response
     */
    public static function postJson(string $url, array $data, array $opt = [], ?array &$cookie = null): Response {
        $opt['method'] = 'POST';
        $opt['type'] = 'json';
        return self::request($url, $data, $opt, $cookie);
    }

    /**
     * --- 发起请求 ---
     * @param string $url 提交的 url
     * @param array|null $data 提交的 data 数据
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, reuse, headers
     * @param array|null $cookie
     * @return Response
     */
    public static function request(string $url, ?array $data = null, array $opt = [], ?array &$cookie = null): Response {
        $uri = parse_url($url);
        $isSsl = false;
        $method = isset($opt['method']) ? strtoupper($opt['method']) : 'GET';
        $type = isset($opt['type']) ? strtolower($opt['type']) : 'form';
        $timeout = isset($opt['timeout']) ? $opt['timeout'] : 5;
        $follow = isset($opt['follow']) ? $opt['follow'] : false;
        $hosts = isset($opt['hosts']) ? $opt['hosts'] : null;
        // $raw = isset($opt['raw']) ? $opt['raw'] : false; // --- 不应该依赖 raw，依赖本服务器的压缩 ---
        $save = isset($opt['save']) ? $opt['save'] : null;      // 直接保存到文件
        $reuse = isset($opt['reuse']) ? $opt['reuse'] : false;  // 是否连接复用
        $headers = [];
        if (isset($opt['headers'])) {
            foreach ($opt['headers'] as $key => $val) {
                $headers[strtolower($key)] = $val;
            }
        }
        if (!isset($headers['user-agent'])) {
            $headers['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36';
        }
        if ($reuse) {
            $ch = self::getLink($uri['host'] . (isset($uri['port']) ? ':' . $uri['port'] : ''));
        } else {
            $ch = curl_init();
        }
        if ($method == 'GET') {
            curl_setopt($ch, CURLOPT_URL, $url . ($data !== null ? '?' . http_build_query($data) : ''));
        } else {
            // --- POST ---
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            $upload = false;
            if ($data !== null) {
                foreach ($data as $key => $val) {
                    if (is_array($val)) {
                        foreach ($val as $k => $v) {
                            if ($v instanceof CURLFile) {
                                $upload = true;
                                break;
                            }
                        }
                    } else if ($val instanceof CURLFile) {
                        $upload = true;
                        break;
                    }
                }
                if ($upload === false) {
                    if ($type === 'json') {
                        $data = json_encode($data);
                    } else {
                        $data = http_build_query($data);
                    }
                } else {
                    // --- 处理 DATA ---
                    foreach ($data as $key => $val) {
                        if (!is_array($val)) {
                            continue;
                        }
                        foreach ($val as $k => $v) {
                            $data[$key . '[' . $k . ']'] = $v;
                        }
                        unset($data[$key]);
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // curl_setopt($ch, CURLOPT_HTTP_VERSION, HTTP_VERSION_2_0);
        // --- ssl ---
        if (substr($url, 0, 6) === 'https:') {
            $isSsl = true;
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, LIB_PATH . 'Net/cacert.pem');
        }
        // --- 重定义 IP ---
        if ($hosts) {
            $host = strtolower($uri['host']);
            if (isset($hosts[$host])) {
                $port = (isset($uri['port']) ? $uri['port'] : ($isSsl ? '443' : '80'));
                // curl_setopt($ch, 10243, [$host . ':' . $port . ':' . $hosts[$host]]);                       // --- CURLOPT_CONNECT_TO, CURL 7.49.0 --- 有点问题
                curl_setopt($ch, CURLOPT_RESOLVE, [$host . ':' . $port . ':' . $hosts[$host]]);        // --- CURL 7.21.3 ---
            }
        }
        // --- 设定头部以及判断提交的数据类型 ---
        if ($type === 'json') {
            if (!isset($headers['content-type'])) {
                $headers['content-type'] = 'application/json; charset=utf-8';
            }
        }
        // --- 设置 expect 防止出现 100 continue ---
        if (!isset($headers['expect'])) {
            $headers['expect'] = '';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::_formatHeaderSender($headers));
        // --- cookie 托管 ---
        if ($cookie !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, self::_buildCookieQuery($cookie, $url));
        }
        // --- 直接下载到文件 ---
        /** @var resource $fh */
        $fh = false;
        $resHeaders = '';
        if ($save !== null) {
            $isBody = false;
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$fh, &$save, &$resHeaders, &$isBody) {
                $len = strlen($data);
                if ($isBody) {
                    if ($data !== '') {
                        if (!$fh) {
                            $fh = fopen($save, 'w');
                        }
                        fwrite($fh, $data);
                    }
                } else {
                    $resHeaders .= $data;
                    $pos = strpos($resHeaders, "\r\n\r\n");
                    if ($pos) {
                        $isBody = true;
                        $body = substr($resHeaders, $pos + 4);
                        $resHeaders = substr($resHeaders, 0, $pos);
                        if ($body !== '') {
                            $fh = fopen($save, 'w');
                            fwrite($fh, substr($resHeaders, $pos + 4));
                        }
                    }
                }
                return $len;
            });
        }
        // --- 执行 ---
        $output = curl_exec($ch);
        // --- 如果下载文件了，则关闭 ---
        if ($fh) {
            fclose($fh);
        }
        $res = new Response([
            'error' => curl_error($ch),
            'errno' => curl_errno($ch),
            'info' => curl_getinfo($ch)
        ]);
        if (!$reuse) {
            curl_close($ch);
        }
        // --- 处理返回值 ---
        if ($resHeaders === '') {
            if ($output === false) {
                return $res;
            }
            $sp = strpos($output, "\r\n\r\n");
            $resHeaders = substr($output, 0, $sp);
            $content = substr($output, $sp + 4);
            $res->content = $content;
        } else {
            $res->content = 'OK';
        }
        $res->headers = self::_formatHeader($resHeaders, $url);
        // --- 是否追踪 cookie ---
        if ($cookie !== null) {
            // --- 提取 cookie ---
            self::_buildCookieObject($cookie, isset($res->headers['set-cookie']) ? $res->headers['set-cookie'] : [], $url);
        }
        // --- 判断 follow 追踪 ---
        if (!$follow) {
            return $res;
        }
        if (!isset($res->headers['location'])) {
            return $res;
        }
        $headers['referer'] = $url;
        return self::request(Text::urlResolve($url, $res->headers['location']), $data, [
            'method' => $method,
            'type' => $type,
            'timeout' => $timeout,
            'follow' => $follow,
            'hosts' => $hosts,
            'save' => $save,
            'headers' => $headers
        ], $cookie);
    }

    /**
     * --- 根据 Set-Cookie 头部转换到 cookie 数组（会自动筛掉不能设置的 cookie） ---
     * @param array $cookie Cookie 引用键值对数组
     * @param array $setCookies 头部的 set-cookie 数组
     * @param string $url 当前网址
     */
    private static function _buildCookieObject(array &$cookie, array $setCookies, string $url) {
        $uri = parse_url($url);
        if (!isset($uri['path'])) {
            $uri['path'] = '/';
        }
        foreach ($setCookies as $setCookie) {
            $cookieTmp = [];
            $list = explode(';', $setCookie);
            // --- 提取 set-cookie 中的定义信息 ---
            foreach ($list as $index => $item) {
                $arr = explode('=', $item);
                $key = trim($arr[0]);
                $val = isset($arr[1]) ? $arr[1] : '';
                if ($index === 0) {
                    // --- 用户定义的信息 ---
                    $cookieTmp['name'] = $key;
                    $cookieTmp['value'] = urldecode($val);
                } else {
                    // --- cookie 配置信息，可转小写方便读取 ---
                    $cookieTmp[strtolower($key)] = $val;
                }
            }
            // --- 获取定义的 domain ---
            if (isset($cookieTmp['domain'])) {
                $cookieTmp['domain'] = explode(':', $cookieTmp['domain'])[0];
                if ($cookieTmp['domain'][0] !== '.') {
                    $domain = '.' . $cookieTmp['domain'];
                    $domainN = $cookieTmp['domain'];
                } else {
                    $domain = $cookieTmp['domain'];
                    $domainN = substr($cookieTmp['domain'], 1);
                }
            } else {
                $domain = '.' . $uri['host'];
                $domainN = $uri['host'];
            }
            // --- 判断有没有设置 domain 的权限 ---
            // --- $uri['host'] vs  $domain($domainN) ---
            // --- ok.xxx.com   vs  .ok.xxx.com: true ---
            // --- ok.xxx.com   vs  .xxx.com: true ---
            // --- z.ok.xxx.com vs  .xxx.com: true ---
            // --- ok.xxx.com   vs  .zz.ok.xxx.com: false ---
            if ($uri['host'] !== $domainN) {
                $domainSc = substr_count($domain, '.');
                if ($domainSc <= 1) {
                    // --- .com ---
                    continue;
                }
                // --- 判断访问路径 (uri['host']) 是不是设置域名 (domain) 的孩子，domain 必须是 uriHost 的同级或者父辈 ---
                if (substr_count($uri['host'], '.') < $domainSc) {
                    // --- ok.xxx.com (2) < .pp.xxx.com (2): false ---
                    // --- ok.xxx.com < .z.xxx.com: false ---
                    continue;
                }
                if (substr($uri['host'], -strlen($domain)) !== $domain) {
                    // --- ok.xxx.com, .ppp.com: false ---
                    continue;
                }
            }
            $cookieKey = $cookieTmp['name'].'-'.$domainN;
            if (isset($cookieTmp['max-age']) && ($cookieTmp['max-age'] <= 0)) {
                if (isset($cookie[$cookieKey])) {
                    unset($cookie[$cookieKey]);
                    continue;
                }
            }
            $exp = -1992199400;
            if (isset($cookieTmp['max-age'])) {
                $exp = $_SERVER['REQUEST_TIME'] + $cookieTmp['max-age'];
            }
            // --- path ---
            $path = isset($cookieTmp['path']) ? $cookieTmp['path'] : '';
            if ($path === '') {
                $srp = strrpos($uri['path'], '/');
                $path = substr($uri['path'], 0, $srp + 1);
            } else if ($path[0] !== '/') {
                $path = '/' . $path;
            }
            $cookie[$cookieKey] = [
                'name' => $cookieTmp['name'],
                'value' => $cookieTmp['value'],
                'exp' => $exp,
                'path' => $path,
                'domain' => $domainN,
                'secure' => isset($cookieTmp['secure']) ? true : false
            ];
        }
    }

    /**
     * --- 数组转换为 Cookie 拼接字符串（会自动筛掉不能发送的 cookie） ---
     * @param array $cookie Cookie 键值数组
     * @param string $url 当前网页路径
     * @return string
     */
    private static function _buildCookieQuery(array &$cookie, string $url): string {
        $cookieStr = '';
        foreach ($cookie as $key => $item) {
            if (($item['exp'] < $_SERVER['REQUEST_TIME']) && ($item['exp'] !== -1992199400)) {
                unset($cookie[$key]);
                continue;
            }
            $uri = parse_url($url);
            if (!isset($uri['path'])) {
                $uri['path'] = '/';
            }
            if ($item['secure'] && (strtolower($uri['scheme']) === 'http')) {
                continue;
            }
            // --- 判断 path 是否匹配 ---
            if (substr($uri['path'], 0, strlen($item['path'])) !== $item['path']) {
                continue;
            }
            $domain = '.' . $item['domain'];
            // --- 判断 $uri['host'] 必须是 $domain 的同级或子级 ---
            // --- $uri['host']     vs      $domain ---
            // --- ok.xxx.com       vs      .ok.xxx.com: true ---
            // --- ok.xxx.com       vs      .xxx.com: true ---
            // --- z.ok.xxx.com     vs      .xxx.com: true ---
            // --- ok.xxx.com       vs      .zz.ok.xxx.com: false ---
            if ('.' . $uri['host'] !== $domain) {
                // --- 判断自己是不是孩子 ---
                if (substr_count($uri['host'], '.') < substr_count($domain, '.')) {
                    // --- ok.xxx.com, .zz.ok.xxx.com: false ---
                    // --- pp.ok.xxx.com, .zz.ok.xxx.com: false ---
                    // --- q.b.ok.xx.com, .zz.ok.xxx.com: true ---
                    continue;
                }
                if (substr($uri['host'], -strlen($domain)) !== $domain) {
                    // --- q.b.ok.xx.com, .zz.ok.xxx.com: false ---
                    // --- z.ok.xxx.com, .xxx.com: true ---
                    continue;
                }
            }
            $cookieStr .= $item['name'] . '=' . urlencode($item['value']) . ';';
        }
        if ($cookieStr != '') {
            return substr($cookieStr, 0, -1);
        } else {
            return '';
        }
    }

    /**
     * --- 将获取的 header 字符串格式化为数组 ---
     * @param string $header
     * @param string $url 当前访问的 url
     * @return array
     */
    private static function _formatHeader(string $header, string $url) {
        $h = [];
        $header = explode("\r\n", $header);
        foreach ($header as $val) {
            $sp = strpos($val, ': ');
            if (!$sp) {
                preg_match('/HTTP\\/([0-9.]+) ([0-9]+)/', $val, $match);
                $h['http-version'] = $match[1];
                $h['http-code'] = $match[2];
                $h['http-url'] = $url;
                continue;
            }
            $k = strtolower(substr($val, 0, $sp));
            if ($k === 'set-cookie') {
                if (!isset($h[$k])) {
                    $h[$k] = [];
                }
                $h[$k][] = substr($val, $sp + 2);
            } else {
                $h[$k] = substr($val, $sp + 2);
            }
        }
        return $h;
    }

    /**
     * --- 将 kv 格式的 header 转换为 curl 提交时的 header ---
     * @param array $headers
     * @return array
     */
    private static function _formatHeaderSender(array $headers) {
        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = $k . ': ' . $v;
        }
        return $h;
    }

    /**
     * --- 模拟重启浏览器后的状态 ---
     * @param array $cookie
     */
    public static function resetCookieSession(array &$cookie) {
        foreach ($cookie as $key => $item) {
            if ($item['exp'] === -1992199400) {
                unset($cookie[$key]);
            }
        }
    }

    /**
     * --- 获取 IP （非安全 IP）---
     * @return string
     */
    public static function getIP(): string {
        if (isset($_SERVER['HTTP_X_REAL_FORWARDED_FOR']) && $_SERVER['HTTP_X_REAL_FORWARDED_FOR'] && ($_SERVER['HTTP_X_REAL_FORWARDED_FOR'] != '0.0.0.0')) {
            return $_SERVER['HTTP_X_REAL_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && ($_SERVER['HTTP_X_FORWARDED_FOR'] != '0.0.0.0')) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] && ($_SERVER['HTTP_CLIENT_IP'] != '0.0.0.0')) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_CONNECTING_IP']) && $_SERVER['HTTP_X_CONNECTING_IP'] && ($_SERVER['HTTP_X_CONNECTING_IP'] != '0.0.0.0')) {
            return $_SERVER['HTTP_X_CONNECTING_IP'];
        } else if (isset($_SERVER['HTTP_CF_CONNECTING_IP']) && $_SERVER['HTTP_CF_CONNECTING_IP'] && ($_SERVER['HTTP_CF_CONNECTING_IP'] != '0.0.0.0')) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } else {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
    }

    /** @var string HTTP_X_CONNECTING_IP */
    public const REAL_IP_HEADER_X = 'HTTP_X_CONNECTING_IP';
    /** @var string HTTP_CF_CONNECTING_IP */
    public const REAL_IP_HEADER_CF = 'HTTP_CF_CONNECTING_IP';
    /**
     * --- 獲取直連 IP（安全 IP） ---
     * @param string $name 输入安全的 header
     * @return string
     */
    public static function getRealIP($name = ''): string {
        if ($name === '') {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
        if (isset($_SERVER[$name]) && $_SERVER[$name] && ($_SERVER[$name] != '0.0.0.0')) {
            return $_SERVER[$name];
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

}

