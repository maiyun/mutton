<?php
/**
 * CA: https://curl.haxx.se/ca/cacert.pem
 * User: JianSuoQiYue
 * Date: 2015/10/26 14:23
 * Last: 2018-6-30 21:58:34
 */
declare(strict_types = 1);

namespace lib;

class Net {

    private static $_error = '';
    private static $_errno = 0;
    private static $_info = NULL;

    public static function getError() {
        return self::$_error;
    }
    public static function getErrno() {
        return self::$_errno;
    }
    public static function getInfo() {
        return self::$_info;
    }

    /**
     * @param string $url
     * @param array $opt
     * @param array|null $cookie
     * @return array|bool|string
     * @throws \Exception
     */
    public static function get(string $url, array $opt = [], ?array &$cookie = NULL) {

        $opt['url'] = $url;
        return self::request($opt, $cookie);

    }

    /**
     * @param string $url
     * @param array|string $data
     * @param array $opt
     * @param array|null $cookie
     * @return array|bool|string
     * @throws \Exception
     */
    public static function post(string $url, $data, array $opt = [], ?array &$cookie = NULL) {

        $opt['url'] = $url;
        $opt['data'] = $data;
        $opt['method'] = 'POST';

        return self::request($opt, $cookie);

    }

    // --- GET, POST 基函数 ---

    /**
     * @param array $opt
     * @param array|null $cookie
     * @return bool|string|array
     * @throws \Exception
     */
    public static function request(array $opt, ?array &$cookie = NULL) {
        $method = isset($opt['method']) && strtoupper($opt['method']) == 'POST' ? 'POST' : 'GET';
        $url = isset($opt['url']) ? $opt['url'] : '';
        if ($cookie !== NULL) {
            if (!is_array($cookie)) {
                throw new \Exception('[Error] cookie type not support.');
            }
        }

        if ($url != '') {
            if ($method == 'GET') {
                $ch = curl_init($url . (isset($opt['data']) ? '?' . http_build_query($opt['data']) : ''));
            } else {
                // --- POST ---
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                $upload = false;
                if (isset($opt['data'])) {
                    if (is_array($opt['data'])) {
                        foreach ($opt['data'] as $i) {
                            if (isset($i[0]) && ($i[0] == '@')) {
                                $upload = true;
                                break;
                            }
                        }
                        if ($upload === false) {
                            if (isset($opt['json']) && $opt['json']) {
                                $opt['data'] = json_encode($opt['data']);
                            } else {
                                $opt['data'] = http_build_query($opt['data']);
                            }
                        }
                    }
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $opt['data']);
                }
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.79 Safari/537.36');
            // --- ssl ---
            if (substr($url, 0, 6) == 'https:') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_CAINFO, LIB_PATH . 'Net/cacert.pem');
            }
            // --- 自定义头部 ---
            if (isset($opt['headers'])) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $opt['headers']);
            }
            // --- cookie 托管 ---
            if ($cookie !== NULL) {
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_COOKIE, self::_cookieBuildQuery($cookie));
            }
            // --- 返回头部 ---
            if (isset($opt['resHeader']) && $opt['resHeader'] && $cookie === NULL) {
                curl_setopt($ch, CURLOPT_HEADER, true);
            }
            // --- 检测有没有更多额外的 curl 定义项目 ---
            foreach ($opt as $key => $val) {
                if (is_int($key)) {
                    curl_setopt($ch, $key, $val);
                }
            }
            // --- 执行 ---
            $output = curl_exec($ch);
            self::$_error = curl_error($ch);
            self::$_errno = curl_errno($ch);
            self::$_info = curl_getinfo($ch);
            curl_close($ch);
            // --- 处理返回值 ---
            if ($output !== false) {
                if (($cookie !== NULL) || (isset($opt['resHeader']) && $opt['resHeader'])) {
                    $sp = strpos($output, "\r\n\r\n");
                    $header = substr($output, 0, $sp);
                    $content = substr($output, $sp + 4);
                    if ($cookie !== NULL) {
                        // --- 提取 cookie ---
                        preg_match_all('/Set-Cookie:(.+?);/i', $header, $matchList);
                        foreach ($matchList[1] as $match) {
                            list($key, $val) = explode('=', trim($match));
                            if ($val == 'deleted') {
                                if (isset($cookie[$key])) {
                                    unset($cookie[$key]);
                                }
                            } else {
                                $cookie[$key] = [
                                    'value' => $val
                                ];
                            }
                        }
                        if (isset($opt['resHeader']) && $opt['resHeader']) {
                            return [
                                'header' => $header,
                                'content' => $content
                            ];
                        } else {
                            return $content;
                        }
                    } else {
                        return [
                            'header' => $header,
                            'content' => $content
                        ];
                    }
                } else {
                    return $output;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function getIP() {

        if (getenv('HTTP_CLIENT_IP')) {
            return getenv('HTTP_CLIENT_IP');
        } else if (getenv('HTTP_X_FORWARDED_FOR')) {
            return getenv('HTTP_X_FORWARDED_FOR');
        } else {
            return getenv('REMOTE_ADDR');
        }

    }

    public static function mail($server, $from, $nickname, $to, $title, $content) {

        if (!preg_match('/\w[a-zA-Z0-9\.\-\+]*\@(\w+[a-zA-Z0-9\-\.]+\w)/i', $to, $ms)) {
            return ['success' => false, 'error' => 'Email address invalid'];
        }
        if (!getmxrr($ms[1], $mx)) {
            return ['success' => false, 'error' => 'MX record of host not found!'];
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
        return ['success' => true];

    }

    // --- 类内部工具 ---

    // --- 数组转换为 Cookie ---
    private static function _cookieBuildQuery($cookieArray) {
        $cookie = '';
        foreach ($cookieArray as $key => $item) {
            $cookie .= $key . '=' . $item['value'] . ';';
        }
        if ($cookie != '') {
            return substr($cookie, 0, -1);
        } else {
            return '';
        }
    }

}

