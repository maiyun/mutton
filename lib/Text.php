<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.1","folder":false} - END
 * Date: 2015/05/07 13:50
 * Last: 2019-6-7 13:10:04, 2020-1-17 00:56:44,  2020-1-26 23:18:42
 */
declare(strict_types = 1);

namespace lib;

class Text {

    // --- 随机 ---
    const RANDOM_N = '0123456789';
    const RANDOM_U = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const RANDOM_L = 'abcdefghijklmnopqrstuvwxyz';

    const RANDOM_UN = self::RANDOM_U . self::RANDOM_N;
    const RANDOM_LN = self::RANDOM_L . self::RANDOM_N;
    const RANDOM_LU = self::RANDOM_L . self::RANDOM_U;
    const RANDOM_LUN = self::RANDOM_L . self::RANDOM_U . self::RANDOM_N;
    const RANDOM_V = 'ACEFGHJKLMNPRSTWXY34567';
    const RANDOM_LUNS = self::RANDOM_LUN . '()`~!@#$%^&*-+=_|{}[]:;\'<>,.?/]';

    /**
     * --- 生成随机字符串 ---
     * @param int $length 长度
     * @param string $source 采样值
     * @return string
     */
    public static function random(int $length = 8, string $source = self::RANDOM_LN): string {
        $len = strlen($source);
        $temp = '';
        for ($i = 0; $i < $length; ++$i) {
            $temp .= $source[rand(0, $len - 1)];
        }
        return $temp;
    }

    /**
     * --- 将文件大小格式化为容量显示字符串 ---
     * @param float $size
     * @param string $spliter
     * @return string
     */
    public static function sizeFormat(float $size, string $spliter = ' '): string {
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
            'protocol' => isset($uri['scheme']) ? strtolower($uri['scheme']) : null,
            'auth' => null,
            'user' => isset($uri['user']) ? $uri['user'] : null,
            'pass' => isset($uri['pass']) ? $uri['pass'] : null,
            'host' => null,
            'port' => isset($uri['port']) ? $uri['port'] : null,
            'hostname' => isset($uri['host']) ? strtolower($uri['host']) : null,
            'hash' => isset($uri['fragment']) ? $uri['fragment'] : null,
            'query' => isset($uri['query']) ? $uri['query'] : null,
            'pathname' => isset($uri['path']) ? $uri['path'] : '/',
            'path' => null
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
     * --- 将虚拟 URL 路径转换为绝对 URL 路径 ---
     * @param string $from 基准路径
     * @param string $to 虚拟路径
     * @return string
     */
    public static function urlResolve(string $from, string $to): string {
        // --- 获取 scheme, host, path ---
        $f = Text::parseUrl($from);
        // --- 以 // 开头的，加上 from 的 protocol 返回 ---
        if (strpos($to,'//') === 0) {
            return $f['protocol'] ? $f['protocol'] . ':' . $to : $to;
        }
        // --- 已经是绝对路径，直接返回 ---
        if (parse_url($to, PHP_URL_SCHEME)) {
            return $to;
        }
        // --- # 或 ? 替换后返回 ---
        if ($to[0] == '#' || $to[0] == '?') {
            $sp = strpos($from, $to[0]);
            if ($sp !== false) {
                return substr($from, 0, $sp) . $to;
            } else {
                return $from . $to;
            }
        }
        // --- 移除不是路径的部分，如 /ab/c 变成了 /ab ---
        $path = preg_replace('#/[^/]*$#', '', $f['path']);
        // --- 相对路径从根路径开始 ---
        if ($to[0] ==  '/') {
            $path = '';
        }
        // --- 非最终绝对网址 ----
        $abs = ($f['host'] ? $f['host'] : '') . $path . '/' . $to;
        // --- 删掉 ./ ---
        $abs = preg_replace('/(\/\.?\/)/', '/', $abs);
        // --- 删掉 ../ ---
        while (true) {
            // --- 用循环法把 /xx/../ 变成 / 进行返回上级目录 ---
            $abs = preg_replace('/\/(?!\.\.)[^\/]+\/\.\.\//', '/', $abs, -1, $count);
            if ($count === 0) {
                break;
            }
        }
        // --- 剩下的 ../ 就是无效的直接替换为空 ---
        $abs = str_replace('../', '', $abs);
        // --- 返回最终结果 ---
        return ($f['protocol'] ? $f['protocol'] . '://' : '') . $abs;
    }

    /**
     * --- 是否是邮件地址 ---
     * @param string $email
     * @return bool
     */
    public static function isEMail(string $email): bool {
        return preg_match('/^[-_\w\\.]+\\@[-_\w]+(\.[-_\w]+)*$/i', $email) ? true : false;
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

    /**
     * --- 获取一个域名的顶级域名 ---
     * @param string $domain
     * @return string
     */
    public static function getHost(string $domain = ''): string {
        if ($domain === '') {
            $domain = $_SERVER['HTTP_HOST'];
        }
        $domainArr = explode('.', $domain);
        $count = count($domainArr);
        // --- 判断是否是双后缀 ---
        $isDoubleExt = false;
        $extList = ['com.cn', 'net.cn', 'org.cn', 'gov.cn', 'co.jp', 'com.tw', 'co.kr', 'co.hk'];
        foreach ($extList as $ext){
            if (strpos($domain, '.' . $ext)){
                $isDoubleExt = true;
                break;
            }
        }
        if ($isDoubleExt) {
            $host = $domainArr[$count - 3] . '.' . $domainArr[$count - 2] . '.' . $domainArr[$count - 1];
        } else {
            $host = $domainArr[$count - 2] . '.' . $domainArr[$count - 1];
        }
        return $host;
    }

    /**
     * --- 传入正则进行匹配 str 是否都满足 ---
     * @param string $str
     * @param array $regs
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
     * @param string $p
     * @return bool
     */
    public static function isPhoneCN(string $p): bool {
        if (preg_match('/^1[0-9]{10}$/', $p)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * --- 是否是中国大陆身份证号码 ---
     * @param string $idcard
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
        for ($i=0; $i<17; $i++) {
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

