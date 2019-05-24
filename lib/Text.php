<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/05/07 13:50
 * Last: 2019-3-13 21:28:21
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

    // --- 显示文件大小格式化 ---
    public static function sizeFormat(float $size, string $spliter = ' '): string {
        static $units = array(
            'Bytes',
            'KB',
            'MB',
            'GB',
            'TB',
            'PB'
        );
        $i = 0;
        for (; $i < 6 && $size >= 1024.0; ++$i) {
            $size /= 1024.0;
        }
        return round($size, 2) . $spliter . $units[$i];
    }

    /**
     * --- 将虚拟 URL 路径转换为绝对 URL 路径 ---
     * @param string $from 基准路径
     * @param string $to 虚拟路径
     * @return string
     */
    public static function urlResolve(string $from, string $to): string {
        // --- 获取 scheme, host, path ---
        $f = parse_url($from);
        // --- 以 // 开头的，加上 from 的 scheme 返回 ---
        if (strpos($to,'//') === 0) {
            return $f['scheme'] . ':' . $to;
        }
        // --- 已经是绝对路径，直接返回 ---
        if (parse_url($to, PHP_URL_SCHEME) != '' ) {
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
        $abs = $f['host'] . $path . '/' . $to;
        // --- 删掉 ./ ---
        $abs = preg_replace('/(\/\.?\/)/', '/', $abs);
        // --- 删掉 ../ ---
        while (true) {
            $abs = preg_replace('/\/(?!\.\.)[^\/]+\/\.\.\//', '/', $abs, -1, $count);
            if ($count === 0) {
                break;
            }
        }
        $abs = str_replace('../', '', $abs);
        // --- 返回最终结果 ---
        return $f['scheme'] . '://' . $abs;
    }

    // --- 是否是邮件 ---
    public static function isEMail(string $email): bool {
        return preg_match('/^[-_\w\.]+\@[-_\w]+(\.[-_\w]+)*$/i', $email) ? true : false;
    }

    // --- 是否是 IP ---
    public static function isIPv4(string $ip): bool {
        return preg_match('/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/', $ip) ? true : false;
    }

    /**
     * --- 换行替换为空 ---
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
     * --- 获取顶级域名 ---
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
        foreach($extList as $ext){
            if(strpos($domain, '.'.$ext)){
                $isDoubleExt = true;
                break;
            }
        }
        if($isDoubleExt){
            $host = $domainArr[$count - 3] . '.' . $domainArr[$count - 2] . '.' . $domainArr[$count - 1];
        } else {
            $host = $domainArr[$count - 2] . '.' . $domainArr[$count - 1];
        }
        return $host;
    }

    /**
     * --- 匹配正则数组 ---
     * @param string $str
     * @param array $reg
     * @return bool
     */
    public static function match(string $str, array $reg): bool {
        foreach ($reg as $val) {
            if (preg_match($val, $str)) {
                return true;
            }
        }
        return false;
    }

    // --- 以下是适用于中国大陆的方法 ---

    // --- 是否是中国大陆的手机号 ---
    public static function isPhoneCN(string $p): bool {
        if (preg_match('/^1[0-9]{10}$/', $p)) {
            return true;
        } else {
            return false;
        }
    }

    // --- 返回手机号是中国哪家运营商 ---
    public static function phoneSPCN(string $p): int {
        $list = [
            // --- 移动 ---
            ['^13[4|5|6|7|8|9]\d{8}$', 0],
            ['^15[0|1|2|7|8|9]\d{8}$', 0],
            ['^18[2|3|4|7|8]\d{8}$', 0],
            ['^147\d{8}$', 0],
            ['^1705\d{7}$', 0],
            ['^178\d{8}$', 0],
            // --- 联通 ---
            ['^13[0|1|2]\d{8}$', 1],
            ['^15[5|6]\d{8}$', 1],
            ['^18[5|6]\d{8}$', 1],
            ['^145\d{8}$', 1],
            ['^1709\d{7}$', 1],
            ['^1708\d{7}$', 1],
            ['^1707\d{7}$', 1],
            ['^176\d{8}$', 1],
            // --- 电信 ---
            ['^133\d{8}$', 2],
            ['^153\d{8}$', 2],
            ['^18[0|1|9]\d{8}$', 2],
            ['^1700\d{7}$', 2],
            ['^177\d{8}$', 2]
        ];
        foreach($list as $item) {
            if (preg_match('/'.$item[0].'/', $p)) {
                return $item[1];
            }
        }
        return -1;
    }

    // --- 根据中国手机号运营商分组 ---
    public static function phoneSPGroupCN(array $pList): array {
        $list = ['0' => [], '1' => [], '2' => [], '-1' => []];
        foreach ($pList as $p) {
            if (($r = self::phoneSPCN($p)) !== false) {
                $list[(string)$r][] = $p;
            } else {
                $list['-1'][] = $p;
            }
        }
        $list = array_filter($list, function($v) {
            if (count($v) === 0 || $v === '') return false;
            return true;
        });
        return $list;
    }

    // --- 是否是中国大陆身份证 ---

    public static function isIdCardCN(string $idcard): bool {
        if(strlen($idcard) != 18) {
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
        if($verifyCode == $verifyCodeList[$mod]) {
            return true;
        } else {
            return false;
        }
    }

}

