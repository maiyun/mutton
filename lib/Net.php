<?php
/**
 * CA: https://curl.haxx.se/ca/cacert.pem
 * User: JianSuoQiYue
 * Date: 2015/10/26 14:23
 * Last: 2019-3-13 17:33:39
 */
declare(strict_types = 1);

namespace lib;

use lib\Net\Request;
use lib\Net\Response;

class Net {

    public static function get(string $url, ?Request $req = NULL, ?array &$cookie = NULL) {
        return self::request($url, NULL, $req, $cookie);
    }

    public static function post(string $url, array $data, ?Request $req = NULL, ?array &$cookie = NULL) {
        if ($req === NULL) {
            $req = Request::get([
                'method' => 'POST'
            ]);
        }
        return self::request($url, $data, $req, $cookie);
    }

    public static function postJson(string $url, array $data, ?Request $req = NULL, ?array &$cookie = NULL): Response {
        if ($req === NULL) {
            $req = Request::get([
                'method' => 'POST',
                'type' => 'json'
            ]);
        }
        return self::request($url, $data, $req, $cookie);
    }

    // --- GET, POST 基函数 ---
    public static function request(string $url, ?array $data = NULL, ?Request $req = NULL, ?array &$cookie = NULL): Response {
        if ($req === NULL) {
            $req = Request::get();
        }
        $method = $req->getMethod();
        if ($url != '') {
            if ($method == 'GET') {
                $ch = curl_init($url . ($data !== NULL ? '?' . http_build_query($data) : ''));
            } else {
                // --- POST ---
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                $upload = false;
                if ($data !== NULL) {
                    foreach ($data as $i) {
                        if (isset($i[0]) && ($i[0] == '@')) {
                            $upload = true;
                            break;
                        }
                    }
                    if ($upload === false) {
                        if ($req->getType() === 'json') {
                            $data = json_encode($data);
                        } else {
                            $data = http_build_query($data);
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_TIMEOUT, $req->getTimeout());
            curl_setopt($ch, CURLOPT_USERAGENT, $req->getUserAgent());
            // --- ssl ---
            if (substr($url, 0, 6) == 'https:') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_CAINFO, LIB_PATH . 'Net/cacert.pem');
            }
            // --- 自定义头部 ---
            if ($req->getHttpHeader() !== NULL) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $req->getHttpHeader());
            }
            // --- 上级页面 ---
            if ($req->getReferer() !== '') {
                curl_setopt($ch, CURLOPT_REFERER, $req->getReferer());
            }
            // --- cookie 托管 ---
            if ($cookie !== NULL) {
                curl_setopt($ch, CURLOPT_COOKIE, self::_cookieBuildQuery($cookie, $url));
            }
            // --- 检测有没有更多额外的 curl 定义项目 ---
            if (($curlOpt = $req->getCurlOpt()) !== NULL) {
                foreach ($curlOpt as $key => $val) {
                    if (is_int($key)) {
                        curl_setopt($ch, $key, $val);
                    }
                }
            }
            // --- 执行 ---
            $output = curl_exec($ch);
            $res = Response::get([
                'error' => curl_error($ch),
                'errNo' => curl_errno($ch),
                'errInfo' => curl_getinfo($ch)
            ]);
            curl_close($ch);
            // --- 处理返回值 ---
            if ($output !== false) {
                $sp = strpos($output, "\r\n\r\n");
                $header = substr($output, 0, $sp);
                $content = substr($output, $sp + 4);
                $res->header = $header;
                $res->content = $content;
                if ($cookie !== NULL) {
                    // --- 提取 cookie ---
                    preg_match_all('/Set-Cookie:(.+?)\r\n/i', $header, $matchList);
                    $uri = parse_url($url);
                    foreach ($matchList[1] as $match) {
                        $cookieTmp = [];
                        $list = explode(';', $match);
                        foreach ($list as $index => $item) {
                            $arr = explode('=', $item);
                            $key = $arr[0];
                            $val = isset($arr[1]) ? $arr[1] : '';
                            if ($index === 0) {
                                $cookieTmp['name'] = trim($key);
                                $cookieTmp['value'] = urldecode($val);
                            } else {
                                $cookieTmp[trim(strtolower($key))] = $val;
                            }
                        }
                        if (isset($cookieTmp['domain'])) {
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
                        // --- ok.xxx.com, .ok.xxx.com: true ---
                        // --- ok.xxx.com, .xxx.com: true ---
                        // --- z.ok.xxx.com, .xxx.com: true ---
                        // --- ok.xxx.com, .zz.ok.xxx.com: false ---
                        $go = true;
                        if ('.' . $uri['host'] !== $domain) {
                            $domainSc = substr_count($domain, '.');
                            if ($domainSc > 1) {
                                // --- 判断自己是不是孩子 ---
                                if (substr_count($uri['host'], '.') >= $domainSc) {
                                    if (substr($uri['host'], -strlen($domain)) !== $domain) {
                                        $go = false;
                                    }
                                } else {
                                    $go = false;
                                }
                            } else {
                                $go = false;
                            }
                        }
                        if ($go) {
                            $cookieKey = $cookieTmp['name'].'-'.$domainN;
                            if (isset($cookieTmp['max-age']) && ($cookieTmp['max-age'] <= 0)) {
                                if (isset($cookie[$cookieKey])) {
                                    unset($cookie[$cookieKey]);
                                    $go = false;
                                }
                            }
                            if ($go) {
                                $exp = 0;
                                if (isset($cookieTmp['max-age'])) {
                                    $exp = $_SERVER['REQUEST_TIME'] + $cookieTmp['max-age'];
                                }
                                $cookie[$cookieKey] = [
                                    'name' => $cookieTmp['name'],
                                    'value' => $cookieTmp['value'],
                                    'exp' => $exp,
                                    'path' => isset($cookieTmp['path']) ? $cookieTmp['path'] : '',
                                    'domain' => $domainN,
                                    'secure' => isset($cookieTmp['secure']) ? true : false
                                ];
                            }
                        }
                    }
                }
            }
            // --- 判断 follow 追踪 ---
            if (!$req->getFollowLocation()) {
                return $res;
            }
            if (!preg_match('/Location: (.+?)\\r\\n/', $res->header, $matches)) {
                return $res;
            }
            return self::request($matches[1], $data, $req, $cookie);
        } else {
            return Response::get();
        }
    }

    // --- 数组转换为 Cookie ---
    private static function _cookieBuildQuery(array &$cookie, string $url): string {
        $cookieStr = '';
        foreach ($cookie as $key => $item) {
            $go = true;
            if ($item['exp'] > 0) {
                if ($item['exp'] < $_SERVER['REQUEST_TIME']) {
                    unset($cookie[$key]);
                    $go = false;
                }
            }
            if ($go) {
                $uri = parse_url($url);
                if ($item['secure'] && (strtolower($uri['scheme']) === 'http')) {
                    $go = false;
                }
                if ($go) {
                    if ($item['path'] !== '') {
                        $uri['path'] = isset($uri['path']) ? $uri['path'] : '/';
                        if (substr($uri['path'], 0, strlen($item['path'])) !== $item['path']) {
                            $go = false;
                        }
                    }
                    if ($go) {
                        $domain = '.' . $item['domain'];
                        // --- 判断访问域名是不是同级，子级 ---
                        // --- ok.xxx.com, .ok.xxx.com: true ---
                        // --- ok.xxx.com, .xxx.com: true ---
                        // --- z.ok.xxx.com, .xxx.com: false ---
                        // --- ok.xxx.com, .zz.ok.xxx.com: false ---
                        if ('.' . $uri['host'] !== $domain) {
                            // --- 判断自己是不是孩子 ---
                            if (substr_count($uri['host'], '.') === substr_count($domain, '.')) {
                                if (substr($uri['host'], -strlen($domain)) !== $domain) {
                                    $go = false;
                                }
                            } else {
                                $go = false;
                            }
                        }
                        if ($go) {
                            $cookieStr .= $item['name'] . '=' . urlencode($item['value']) . ';';
                        }
                    }
                }
            }
        }
        if ($cookieStr != '') {
            return substr($cookieStr, 0, -1);
        } else {
            return '';
        }
    }

    /**
     * --- 获取 IP ---
     * @return string
     */
    public static function getIP(): string {
        if (getenv('HTTP_CLIENT_IP')) {
            return getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            return getenv('HTTP_X_FORWARDED_FOR');
        } else {
            return getenv('REMOTE_ADDR');
        }
    }

    /**
     * --- 无需 SMTP 服务器发送邮件 ---
     * @param string $server
     * @param string $from
     * @param string $nickname
     * @param string $to
     * @param string $title
     * @param string $content
     * @return bool
     * @throws \Exception
     */
    public static function mail(string $server, string $from, string $nickname, string $to, string $title, string $content): bool {
        if (!preg_match('/\w[a-zA-Z0-9\.\-\+]*\@(\w+[a-zA-Z0-9\-\.]+\w)/i', $to, $ms)) {
            throw new \Exception( 'Email address invalid.');
        }
        if (!getmxrr($ms[1], $mx)) {
            throw new \Exception( 'MX record of host not found.');
        }
        $mx = $mx[0];

        $commands = ['HELO '.$server, 'MAIL FROM:<'.$from.'@'.$server.'>', 'RCPT TO:<'.$to.'>', 'DATA', 'content', 'QUIT'];
        $contents = [
            'MIME-Version: 1.0',
            'Delivered-To: '.$to,
            'Subject: =?UTF-8?B?'.base64_encode($title).'?=',
            'From: =?UTF-8?B?'.base64_encode($nickname).'?= <'.$from.'@'.$server.'>',
            'To: '.$to,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode($content)
        ];
        $fp = fsockopen($mx, 25);
        foreach($commands as $c) {
            if ($c == 'content') {
                $content = join("\r\n", $contents)."\r\n.\r\n";
                fwrite($fp, $content);
            } else {
                fwrite($fp, $c."\r\n");
            }
            //$r = fgets($fp);
        }
        fclose($fp);
        return true;
    }

}

