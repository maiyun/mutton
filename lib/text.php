<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2015/05/07 13:50
 * TLD: https://raw.githubusercontent.com/lupomontero/psl/master/data/rules.json
 * Last: 2019-6-7 13:10:04, 2020-1-17 00:56:44, 2020-03-21 16:23:45
 */
declare(strict_types = 1);

namespace lib;

class Text {

    /**
     * --- 将文件大小格式化为带单位的字符串 ---
     * @param float|int $size
     * @param string $spliter
     * @return string
     */
    public static function sizeFormat($size, string $spliter = ' '): string {
        static $units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;
        for (; $i < 6 && $size >= 1024.0; ++$i) {
            $size /= 1024.0;
        }
        return round($size, 2) . $spliter . $units[$i];
    }

    /**
     * --- 格式化一段 URL ---
     * @param string $url
     * @return array
     */
    public static function parseUrl(string $url): array {
        $uri = parse_url($url);
        $rtn = [
            'protocol' => isset($uri['scheme']) ? strtolower($uri['scheme']) . ':' : null,
            'auth' => null,
            'user' => isset($uri['user']) ? $uri['user'] : null,
            'pass' => isset($uri['pass']) ? $uri['pass'] : null,
            'host' => null,
            'hostname' => isset($uri['host']) ? strtolower($uri['host']) : null,
            'port' => isset($uri['port']) ? $uri['port'] : null,
            'pathname' => isset($uri['path']) ? $uri['path'] : '/',
            'path' => null,
            'query' => isset($uri['query']) ? $uri['query'] : null,
            'hash' => isset($uri['fragment']) ? $uri['fragment'] : null
        ];
        if ($rtn['user']) {
            $rtn['auth'] = $rtn['user'] . ($rtn['pass'] ? ':' . $rtn['pass'] : '');
        }
        if ($rtn['hostname']) {
            $rtn['host'] = $rtn['hostname'] . ($rtn['port'] ? ':' . $rtn['port'] : '');
        }
        $rtn['path'] = $rtn['pathname'] . ($rtn['query'] ? '?' . $rtn['query'] : '');
        return $rtn;
    }

    /**
     * --- 将相对路径根据基准路径进行转换 ---
     * @param string $from 基准路径
     * @param string $to 相对路径
     * @return string
     */
    public static function urlResolve(string $from, string $to): string {
        $from = str_replace('\\', '/', $from);
        $to = str_replace('\\', '/', $to);
        // --- to 为空，直接返回 form ---
        if ($to === '') {
            return self::urlAtom($from);
        }
        // --- 获取 from 的 scheme, host, path ---
        $f = self::parseUrl($from);
        // --- 以 // 开头的，加上 from 的 protocol 返回 ---
        if (strpos($to, '//') === 0) {
            return $f['protocol'] ? $f['protocol'] . $to : $to;
        }
        if ($f['protocol']) {
            // --- 获取小写的 protocol ---
            $from = $f['protocol'] . substr($from, strlen($f['protocol']));
        }
        // --- 获取 to 的 scheme, host, path ---
        $t = self::parseUrl($to);
        // --- 已经是绝对路径，直接返回 ---
        if ($t['protocol']) {
            // --- 获取小写的 protocol ---
            return $t['protocol'] . substr($to, strlen($t['protocol']));
        }
        // --- # 或 ? 替换后返回 ---
        if ($to[0] === '#' || $to[0] === '?') {
            $sp = strpos($from, $to[0]);
            if ($sp !== false) {
                return self::urlAtom(substr($from, 0, $sp) . $to);
            }
            else {
                return self::urlAtom($from . $to);
            }
        }
        // --- 处理后面的尾随路径 ---
        $abs = ($f['auth'] ? $f['auth'] . '@' : '') . ($f['host'] ? $f['host'] : '');
        if ($to[0] === '/') {
            // -- abs 类似是 /xx/xx ---
            $abs .= $to;
        }
        else {
            // --- to 是 xx/xx 这样的 ---
            // --- 移除基准 path 不是路径的部分，如 /ab/c 变成了 /ab，/ab 变成了 空 ---
            $path = preg_replace('/\/[^\/]*$/', '', $f['pathname']);
            // --- abs 是 /xx/xx 了，因为如果 path 是空，则跟上了 /，如果 path 不为空，也是 / 开头 ---
            $abs .= $path . '/' . $to;
        }
        // --- 返回最终结果 ---
        if ($f['protocol'] && ($f['protocol'] !== 'file:') && !$f['host']) {
            // --- 类似 c:/ ---
            return self::urlAtom($f['protocol'] . $abs);
        } else {
            // --- 类似 http:// ---
            return self::urlAtom(($f['protocol'] ? $f['protocol'] . '//' : '') . $abs);
        }
    }

    /**
     * --- 将路径中的 ../ ./ 都按规范妥善处理 ---
     * @param string $url 基准路径
     */
    public static function urlAtom(string $url): string {
        // --- 删掉 ./ ---
        while (true) {
            $url = preg_replace('/\/\.\//', '/', $url, -1, $count);
            if ($count === 0) {
                break;
            }
        }
        // --- 删掉 ../ ---
        while (true) {
            $url = preg_replace('/\/(?!\.\.)[^\/]+\/\.\.\//', '/', $url, -1, $count);
            if ($count === 0) {
                break;
            }
        }
        $url = preg_replace('/\.\.\//', '', $url);
        return $url;
    }

    /**
     * --- 是否是邮件地址 ---
     * @param string $email
     * @return bool
     */
    public static function isEMail(string $email): bool {
        return preg_match('/^[-_\w.]+@[-_\w.]+\.([a-zA-Z]+)$/i', $email) ? true : false;
    }

    /**
     * --- 是否是 IPv4 ---
     * @param string $ip
     * @return bool
     */
    public static function isIPv4(string $ip): bool {
        return preg_match('/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/', $ip) ? true : false;
    }

    /**
     * --- 是否是 IPv6 ---
     * @param string $ip
     * @return bool
     */
    public static function isIPv6(string $ip): bool {
        return preg_match('/^(\w*?:){2,7}[\w.]*$/', $ip) ? true : false;
    }

    /**
     * --- 判断是否是域名 ---
     * @param string $domain
     * @return bool
     */
    public static function isDomain(string $domain): bool {
        return preg_match('/^.+?\.((?![0-9]).)+$/i', $domain) ? true : false;
    }

    /**
     * --- 换行替换为别的字符 ---
     * @param string $str
     * @param string $to
     * @return string
     */
    public static function nlReplace(string $str, string $to = "\n"): string {
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\r", "\n", $str);
        if ($to !== "\n") {
            $str = str_replace("\n", $to, $str);
        }
        return $str;
    }

    /** @var array|null Tld 列表 */
    private static $_tldList = null;
    /**
     * --- 解析域名并获取 tld/sld/domain/sub ---
     * @param string $domain 域名
     * @return array
     */
    public static function parseDomain(string $domain = ''): array {
        $rtn = [
            'tld' => null,
            'sld' => null,
            'domain' => null,
            'sub' => null
        ];
        if ($domain === '') {
            $domain = $_SERVER['HTTP_HOST'];
        }
        else {
            if (!self::isDomain($domain)) {
                return $rtn;
            }
        }
        $arr = explode('.', $domain);
        $length = count($arr);
        if ($length === 1) {
            $rtn['tld'] = strtolower($arr[0]);
            $rtn['domain'] = $rtn['tld'];
        }
        else {
            if (self::$_tldList === null) {
                self::$_tldList = json_decode(file_get_contents(LIB_PATH . 'text/tld.json'), true);
            }
            $last2 = strtolower($arr[$length - 2] . '.' . $arr[$length - 1]);
            if (in_array($last2, self::$_tldList)) {
                // --- last2 就是 tld ---
                $rtn['tld'] = $last2;
                if ($length === 2) {
                    // --- 没有 sld ---
                    $rtn['domain'] = $last2;
                    return $rtn;
                }
                $rtn['sld'] = strtolower($arr[$length - 3]);
                $rtn['domain'] = $rtn['sld'] . '.' . $rtn['tld'];
                // --- 判断是否有 sub ---
                if ($length === 3) {
                    return $rtn;
                }
                array_splice($arr, -3);
                $rtn['sub'] = strtolower(join('.', $arr));
            }
            else {
                $rtn['tld'] = strtolower($arr[$length - 1]);
                $rtn['sld'] = strtolower($arr[$length - 2]);
                $rtn['domain'] = $rtn['sld'] . '.' . $rtn['tld'];
                // --- 判断是否有 sub ---
                if ($length === 2) {
                    return $rtn;
                }
                array_splice($arr, -2);
                $rtn['sub'] = strtolower(join('.', $arr));
            }
        }
        return $rtn;
    }

    /**
     * --- 传入正则进行匹配 str 是否有一项满足 ---
     * @param string $str 要检测的字符串
     * @param array $regs 正则列表
     * @return bool
     */
    public static function match(string $str, array $regs): bool {
        foreach ($regs as $reg) {
            if (preg_match($reg, $str)) {
                return true;
            }
        }
        return false;
    }

    // --- 以下是适用于中国大陆的方法 ---

    /**
     * --- 判断手机号是否是 11 位，不做真实性校验 ---
     * @param string $p 手机号
     * @return bool
     */
    public static function isPhoneCN(string $p): bool {
        if (preg_match('/^1[0-9]{10}$/', $p)) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * --- 是否是中国大陆身份证号码 ---
     * @param string $idcard 身份证号
     * @return bool
     */
    public static function isIdCardCN(string $idcard): bool {
        if (strlen($idcard) != 18) {
            return false;
        }
        // --- 取出本码 ---
        $idcardBase = substr($idcard, 0, 17);
        // --- 取出校验码 ---
        $verifyCode = substr($idcard, 17, 1);
        // --- 加权因子 ---
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        // --- 校验码对应值 ---
        $verifyCodeList = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        // --- 根据前17位计算校验码 ---
        $total = 0;
        for ($i = 0; $i < 17; $i++) {
            $total += substr($idcardBase, $i, 1) * $factor[$i];
        }
        // --- 取模 ---
        $mod = $total % 11;
        // --- 比较校验码 ---
        if ($verifyCode == $verifyCodeList[$mod]) {
            return true;
        } else {
            return false;
        }
    }

}

