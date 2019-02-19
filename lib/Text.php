<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/05/07 13:50
 * Last: 2019-2-19 22:36:57
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

    // --- 是否是邮件 ---
    public static function isEMail(string $email): bool {
        return preg_match('/^[-_\w\.]+\@[-_\w]+(\.[-_\w]+)*$/i', $email) ? true : false;
    }

    // --- 是否是 IP ---
    public static function isIPv4(string $ip): bool {
        return preg_match('/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/', $ip) ? true : false;
    }

    // --- 换行变成别的 ---
    public static function nlReplace(string $str, $to = ''): string {
        $str = str_replace("\r\n", "\n", $str);
        $str = str_replace("\r", "\n", $str);
        $str = str_replace("\n", $to, $str);
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
            if (($r = self::phoneSP($p)) !== false) {
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

