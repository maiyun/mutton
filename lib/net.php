<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2015/10/26 14:23
 * CA: https://curl.haxx.se/ca/cacert.pem
 * Last: 2019-3-13 17:33:39, 2019-12-28 23:48:06, 2020-3-15 16:07:08, 2020-4-11 22:57:46, 2022-3-25 20:30:12, 2022-08-29 21:12:09, 2024-1-1 22:33:24
 */
declare(strict_types = 1);

namespace lib;

use CURLFile;
use lib\Net\Request;
use lib\Net\Response;
use sys\Ctr;

use function sys\log;

class Net {

    /** @var array 连接池 */
    private static $_pool = [];

    /**
     * --- 获取链接 ---
     * @param string $host
     * @param string|null $local
     * @return resource
     */
    public static function getLink(string $host, ?string $local = null) {
        if ($local === null) {
            $local = '';
        }
        if (isset(self::$_pool[$host . '-' . $local])) {
            return self::$_pool[$host . '-' . $local];
        }
        else {
            self::$_pool[$host . '-' . $local] = curl_init();
            return self::$_pool[$host . '-' . $local];
        }
    }

    /**
     * --- 手段关闭复用连接 ---
     * @param string $host
     * @param string|null $local
     */
    public static function closeLink(string $host, ?string $local = null) {
        if ($local === null) {
            $local = '';
        }
        if (isset(self::$_pool[$host . '-' . $local])) {
            curl_close(self::$_pool[$host . '-' . $local]);
            unset(self::$_pool[$host . '-' . $local]);
        }
    }

    /**
     * --- 关闭所有复用连接 ---
     */
    public static function closeAll() {
        foreach (self::$_pool as $key => $link) {
            curl_close($link);
            unset(self::$_pool[$key]);
        }
    }

    /**
     * --- 创建一个请求对象 ---
     * @param string $u
     * @return Request
     */
    public static function open(string $u) {
        return new Request($u);
    }

    /**
     * --- 发起 GET 请求 ---
     * @param string $u 请求的 URL
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, local, reuse, headers
     * @param array|null $cookie
     * @return Response
     */
    public static function get(string $u, array $opt = [], ?array &$cookie = null) {
        return self::request($u, null, $opt, $cookie);
    }

    /**
     * --- 发起 POST 请求 ---
     * @param string $u 请求的 URL
     * @param array|string $data 要发送的数据（值由 @ 开头则是上传文件）
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, local, reuse, headers
     * @param array|null $cookie
     * @return Response
     */
    public static function post(string $u, $data, array $opt = [], ?array &$cookie = null) {
        $opt['method'] = 'POST';
        return self::request($u, $data, $opt, $cookie);
    }

    /**
     * --- 发起 JSON 请求 ---
     * @param string $u
     * @param array $data
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, local, reuse, headers
     * @param array|null $cookie
     * @return Response
     */
    public static function postJson(string $u, array $data, array $opt = [], ?array &$cookie = null): Response {
        $opt['method'] = 'POST';
        $opt['type'] = 'json';
        return self::request($u, $data, $opt, $cookie);
    }

    /**
     * --- 发起请求 ---
     * @param string $u 提交的 url
     * @param array|string|null $data 提交的 data 数据
     * @param array $opt 参数 method, type, timeout, follow, hosts, save, local, headers, reuse
     * @param array|null $cookie
     * @return Response
     */
    public static function request(string $u, $data = null, array $opt = [], ?array &$cookie = null): Response {
        $uri = parse_url($u);
        $isSsl = false;
        $method = isset($opt['method']) ? strtoupper($opt['method']) : 'GET';
        $type = isset($opt['type']) ? strtolower($opt['type']) : 'form';
        $timeout = isset($opt['timeout']) ? $opt['timeout'] : 10;
        $follow = isset($opt['follow']) ? $opt['follow'] : 0;
        $hosts = isset($opt['hosts']) ? $opt['hosts'] : [];
        // $raw = isset($opt['raw']) ? $opt['raw'] : false;                 // --- 不应该依赖 raw，依赖本服务器的压缩 ---
        $save = isset($opt['save']) ? $opt['save'] : null;                  // --- 直接保存到文件的实体地址 ---
        $local = isset($opt['local']) ? $opt['local'] : null;               // --- 使用的本地网卡 IP ---
        $reuse = isset($opt['reuse']) ? $opt['reuse'] : false;              // --- 是否连接复用 ---
        $headers = [];
        if (isset($opt['headers'])) {
            foreach ($opt['headers'] as $key => $val) {
                $headers[strtolower($key)] = $val;
            }
        }
        if (!isset($headers['user-agent'])) {
            $headers['user-agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36';
        }
        // --- reuse: Mutton: true, Kebab: false ---
        if ($reuse) {
            $ch = self::getLink($uri['host'] . (isset($uri['port']) ? ':' . $uri['port'] : ''), $local);
        }
        else {
            $ch = curl_init();
        }
        // --- DATA ---
        if ($method === 'GET') {
            curl_setopt($ch, CURLOPT_URL, $u . ($data !== null ? '?' . (is_string($data) ? $data : http_build_query($data)) : ''));
        }
        else {
            // --- POST ---
            curl_setopt($ch, CURLOPT_URL, $u);
            curl_setopt($ch, CURLOPT_POST, true);
            $upload = false;
            if ($data !== null) {
                if (is_array($data)) {
                    foreach ($data as $key => $val) {
                        if (is_array($val)) {
                            foreach ($val as $k => $v) {
                                if ($v instanceof CURLFile) {
                                    $upload = true;
                                    break;
                                }
                            }
                        }
                        else if ($val instanceof CURLFile) {
                            $upload = true;
                            break;
                        }
                    }
                    if ($upload === false) {
                        if ($type === 'json') {
                            $data = json_encode($data);
                            $headers['content-type'] = 'application/json; charset=utf-8';
                        }
                        else {
                            $data = http_build_query($data);
                        }
                    }
                    else {
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
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        // curl_setopt($ch, CURLOPT_HTTP_VERSION, HTTP_VERSION_2_0);
        // --- local ---
        if ($local !== null) {
            curl_setopt($ch, CURLOPT_INTERFACE, $local);
        }
        // --- ssl ---
        if (strtolower($uri['scheme']) === 'https') {
            $isSsl = true;
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, LIB_PATH . 'net/cacert.pem');
        }
        // --- 重定义 IP ---
        $host = strtolower($uri['host']);
        if (isset($hosts[$host])) {
            $port = (isset($uri['port']) ? $uri['port'] : ($isSsl ? '443' : '80'));
            // curl_setopt($ch, 10243, [$host . ':' . $port . ':' . $hosts[$host]]);               // --- CURLOPT_CONNECT_TO, CURL 7.49.0 --- 有点问题
            curl_setopt($ch, CURLOPT_RESOLVE, [$host . ':' . $port . ':' . $hosts[$host]]);        // --- CURL 7.21.3 ---
        }
        // --- 设置 expect 防止出现 100 continue ---
        if (!isset($headers['expect'])) {
            $headers['expect'] = '';
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::_formatHeaderSender($headers));
        // --- cookie 托管 ---
        if ($cookie !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, self::_buildCookieQuery($cookie, $uri));
        }
        // --- 直接下载到文件 ---
        /** @var resource $fh */
        $fh = false;
        $resHeaders = '';
        $total = 0;
        if ($save !== null) {
            /** @var boolean $isBody --- 当前是否是 body 写入 --- */
            $isBody = false;
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $dat) use (&$fh, &$save, &$resHeaders, &$isBody, &$total) {
                $len = strlen($dat);
                if ($isBody) {
                    if ($dat !== '') {
                        // --- 不等于空才写入，因此 location 状态的不会被写入，写入了也会被覆盖 ---
                        if (!$fh) {
                            $fh = fopen($save, 'w');
                        }
                        fwrite($fh, $dat);
                        $total += $len;
                    }
                }
                else {
                    $resHeaders .= $dat;
                    $pos = strpos($resHeaders, "\r\n\r\n");
                    if ($pos) {
                        $isBody = true;
                        $body = substr($resHeaders, $pos + 4);
                        $resHeaders = substr($resHeaders, 0, $pos);
                        if ($body !== '') {
                            $fh = fopen($save, 'w');
                            fwrite($fh, $body);
                            $total += strlen($body);
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
                log('[' . $u . ']error:' . $res->error . ',errno:' . $res->errno . ',info:' . json_encode($res->info), '-neterror');
                return $res;
            }
            $sp = strpos($output, "\r\n\r\n");
            $resHeaders = substr($output, 0, $sp);
            $content = substr($output, $sp + 4);
            $res->content = $content;
        }
        else {
            $res->content = (string)$total;
        }
        $res->headers = self::_formatHeader($resHeaders, $u);
        // --- 是否追踪 cookie ---
        if ($cookie !== null) {
            // --- 提取 cookie ---
            self::_buildCookieObject($cookie, isset($res->headers['set-cookie']) ? $res->headers['set-cookie'] : [], $uri);
        }
        // --- 判断 follow 追踪 ---
        if ($follow === 0) {
            return $res;
        }
        if (!isset($res->headers['location'])) {
            return $res;
        }
        // --- 哦，要追踪 ---
        $headers['referer'] = $u;
        return self::request(Text::urlResolve($u, $res->headers['location']), $data, [
            'method' => $method,
            'type' => $type,
            'timeout' => $timeout,
            'follow' => $follow - 1,
            'hosts' => $hosts,
            'save' => $save,
            'reuse' => $reuse,
            'headers' => $headers
        ], $cookie);
    }
    
    /**
     * --- 对 cookie 对象进行操作 ---
     * @param array $cookie 要操作的对象
     * @param string $name 名
     * @param string $value 值
     * @param string $domain 应用网址，如 .xxx.com
     * @param array $opt 选项 ttl, path, domain, ssl, httponly
     */
    public static function setCookie(array &$cookie, string $name, string $value, string $domain, array $opt = []): void {
        $ttl = !isset($opt['ttl']) ? 0 : $opt['ttl'];
        $domain = explode(':', $domain)[0];
        $domainN = $domain[0] === '.' ? substr($domain, 1) : $domain;
        
        $exp = -1992199400;
        if ($ttl) {
            $exp = $_SERVER['REQUEST_TIME'] + $ttl;
        }

        $cookie[$name . '-' . $domainN] = [
            'name' => $name,
            'value' => $value,
            'exp' => $exp,
            'path' => isset($opt['path']) ? $opt['path'] : '/',
            'domain' => $domainN,
            'secure' => isset($opt['ssl']) ? true : false,
            'httponly' => isset($opt['httponly']) ? true : false
        ];
    }

    /**
     * --- 根据 Set-Cookie 头部转换到 cookie 数组（会自动筛掉不能设置的 cookie） ---
     * @param array $cookie Cookie 数组
     * @param array $setCookies 头部的 set-cookie 数组
     * @param array $uri 请求的 URI 对象
     */
    private static function _buildCookieObject(array &$cookie, array $setCookies, array $uri) {
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
                }
                else {
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
                }
                else {
                    $domain = $cookieTmp['domain'];
                    $domainN = substr($cookieTmp['domain'], 1);
                }
            }
            else {
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
                // --- 设置的域名和当前 host 不相等，如果是 IP、无 . 域名，则直接失败 ---
                if (!Text::isDomain($uri['host'])) {
                    continue;
                }
                $parseDomain = Text::parseDomain($domainN);
                if ($parseDomain['tld'] === strtolower($domainN)) {
                    // --- 不能给 tld 设置 cookie ---
                    continue;
                }
                // --- 判断访问路径 (uri['host']) 是不是设置域名 (domain) 的孩子，domain 必须是 uriHost 的同级或者父辈 ---
                if (substr($uri['host'], -strlen($domain)) !== $domain) {
                    // --- false 代表进入了，代表失败 ---
                    // --- ok.xxx.com, .xxx.com: true ---
                    // --- ok.xxx.com, .ppp.com: false ---
                    // --- ok.xxx.com, .p.ok.xxx.com: false ---
                    continue;
                }
            }
            $cookieKey = $cookieTmp['name'] . '-' . $domainN;
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
            }
            else if ($path[0] !== '/') {
                $path = '/' . $path;
            }
            $cookie[$cookieKey] = [
                'name' => $cookieTmp['name'],
                'value' => $cookieTmp['value'],
                'exp' => $exp,
                'path' => $path,
                'domain' => $domainN,
                'secure' => isset($cookieTmp['secure']) ? true : false,
                'httponly' => isset($cookieTmp['httponly']) ? true : false,
            ];
        }
    }

    /**
     * --- 数组转换为 Cookie 拼接字符串（会自动筛掉不能发送的 cookie） ---
     * @param array $cookie Cookie 数组
     * @param array $uri 请求的 URI 数组
     * @return string
     */
    private static function _buildCookieQuery(array &$cookie, array $uri): string {
        $cookieStr = '';
        foreach ($cookie as $key => $item) {
            if (($item['exp'] < $_SERVER['REQUEST_TIME']) && ($item['exp'] !== -1992199400)) {
                unset($cookie[$key]);
                continue;
            }
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
                // --- 域名不相等，那么判断当前域名 host 是不是 domain 的孩子 ---
                if (substr($uri['host'], -strlen($domain)) !== $domain) {
                    // --- false 代表进入，被排除了，因为 cookie 的 domain 和当前 host 后半部分，代表不是 domain 的孩子 ---
                    // --- ok.xxx.com, .zz.ok.xxx.com: false ---
                    // --- pp.ok.xxx.com, .zz.ok.xxx.com: false ---
                    // --- q.b.ok.xx.com, .zz.ok.xxx.com: false ---
                    // --- z.ok.xxx.com, .xxx.com: true ---
                    // --- xx.xxx.com, .ok.xxx.com: false ---
                    continue;
                }
            }
            $cookieStr .= $item['name'] . '=' . urlencode($item['value']) . '; ';
        }
        if ($cookieStr !== '') {
            return substr($cookieStr, 0, -2);
        }
        else {
            return '';
        }
    }

    /**
     * --- 将获取的 header 字符串格式化为数组 ---
     * @param string $header
     * @param string $u 当前访问的 url
     * @return array
     */
    private static function _formatHeader(string $header, string $u) {
        $h = [];
        $header = explode("\r\n", $header);
        foreach ($header as $val) {
            $sp = strpos($val, ': ');
            $spl = 2;
            if ($sp === false) {
                // --- 有些后面再没有跟值的话，就得用这样的分割 ---
                $sp = strpos($val, ':');
                $spl = 1;
            }
            if (!$sp) {
                preg_match('/HTTP\\/([0-9.]+) ([0-9]+)/', $val, $match);
                $h['http-version'] = $match[1];
                $h['http-code'] = (int)$match[2];
                $h['http-url'] = $u;
                continue;
            }
            $k = strtolower(substr($val, 0, $sp));
            if ($k === 'set-cookie') {
                if (!isset($h[$k])) {
                    $h[$k] = [];
                }
                $h[$k][] = substr($val, $sp + $spl);
            }
            else {
                $h[$k] = substr($val, $sp + $spl);
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
     * --- 发起反向代理转发 ---
     * @param array $route 路由映射（不以 / 开头）
     * @param array $opt 配置
     */
    public static function rproxy(
        Ctr $ctr,
        array $route,
        array $opt = []
    ): bool {
        $path = PATH . (QS ? '?' . QS : '');
        foreach ($route as $key => $routev) {
            if (substr($path, 0, strlen($key)) !== $key) {
                continue;
            }
            // --- 找到了，做转发 ---
            $lpath = substr($path, strlen($key));
            $opt['method'] = $_SERVER['REQUEST_METHOD'];
            /** --- 不代理的 header  --- */
            $continueHeaders = ['host', 'connection', 'http-version', 'http-code', 'http-url'];
            if (!isset($opt['headers'])) {
                $opt['headers'] = [];
            }
            $rawheaders = getallheaders();
            $headers = [];
            foreach ($rawheaders as $h => $header) {
                $headers[strtolower($h)] = $header;
            }
            foreach ($headers as $h => $header) {
                if (in_array($h, $continueHeaders)) {
                    continue;
                }
                if (strpos($h, ':') !== false || strpos($h, '(') !== false) {
                    continue;
                }
                $opt['headers'][$h] = $header;
            }
            // --- 发起请求 ---
            $data = $ctr->getPrototype('_input');
            if (strpos($headers['content-type'], 'form-data') !== false) {
                $data = [];
                // --- 字符串 ---
                foreach ($_POST as $key => $val) {
                    if (is_string($val)) {
                        $data[$key] = $val;
                    }
                    else if (is_array($val)) {
                        $data[$key] = [];
                        foreach ($val as $key1 => $val1) {
                            $data[$key][$key1] = $val1;
                        }
                    }
                }
                // --- 文件 ---
                foreach ($_FILES as $key => $val) {
                    if (isset($val['name'])) {
                        $data[$key] = new CURLFile($val['tmp_name'], $val['type'], $val['name']);
                    }
                    else {
                        $data[$key] = [];
                        foreach ($val as $key1 => $val1) {
                            $data[$key][$key1] = new CURLFile($val1['tmp_name'], $val1['type'], $val1['name']);
                        }
                    }
                }
            }
            $rres = self::request($routev . $lpath, $data, $opt);
            if ($rres->error) {
                return false;
            }
            foreach ($rres->headers as $h => $v) {
                if (in_array($h, $continueHeaders)) {
                    continue;
                }
                if (strpos($h, ':') !== false || strpos($h, '(') !== false) {
                    continue;
                }
                if ($h === 'content-encoding') {
                    continue;
                }
                header($h . ': ' . $v);
            }
            http_response_code($rres->headers['http-code']);
            echo $rres->content;
            return true;
        }
        return false;
    }

}

