<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2022-08-27 02:13:04
 * Last: 2022-08-27 02:13:08
 */
declare(strict_types = 1);

namespace lib;

class Core {

    /**
     * --- 设置 cookie ---
     * @param string $name 名
     * @param string $value 值
     * @param array $opt 选项 ttl, path, domain, ssl, httponly
     */
    public static function setCookie(string $name, string $value, array $opt = []): void {
        $ttl = !isset($opt['ttl']) ? 0 : $opt['ttl'];
        setcookie($name, $value, time() + $ttl, isset($opt['path']) ? $opt['path'] : "/", isset($opt['domain']) ? $opt['domain'] : "", isset($opt['ssl']) ? $opt['ssl'] : true, isset($opt['httponly']) ? $opt['httponly'] : true);
    }

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
    public static function random(int $length = 8, string $source = self::RANDOM_LN, string $block = ''): string {
        // --- 剔除 block 字符 ---
        $len = strlen($block);
        if ($len > 0) {
            for ($i = 0; $i < $len; ++$i) {
                $source = str_replace($block[$i], '', $source);
            }
        }
        $len = strlen($source);
        if ($len === 0) {
            return '';
        }
        $temp = '';
        for ($i = 0; $i < $length; ++$i) {
            $temp .= $source[rand(0, $len - 1)];
        }
        return $temp;
    }

    /*
     * --- 生成范围内的随机数，带小数点 ---
     * @param float $min 最小数
     * @param float $max 最大数
     * @param int $prec 保留几位小数
     * @return float
     */
    public static function rand(float $min, float $max, int $prec): float {
        if ($prec < 0) {
            $prec = 0;
        }
        $p = pow(10, $prec);
        return rand((int)($min * $p), (int)($max * $p)) / $p;
    }

    /**
     * --- 获取 MUID ---
     * @param string $key 多样性混合 key，可留空
     * @return string
     */
    public static function muid($time = true, $append = '', $key = ''): string {
        $key = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') .
        (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') .
        (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '') .
        (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') .
        (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '') .
        (isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : '') . 'muid' . $key;
        $key = hash_hmac('md5', $key, 'mu' . rand(0, 100) . 'id');
        if ($time) {
            // --- 用于用户 ID、车辆 ID 等时间不敏感且总量较少的场景 ---
            $date = explode('-', date('Y-m-d-H-i'));
            $y = base_convert($date[0], 10, 36); // --- 3 位数，从 1296 到 46655 年 ---
            $m = base_convert($date[1], 10, 36);
            $d = base_convert($date[2], 10, 36);
            $h = base_convert($date[3], 10, 36);
            $rand = self::random(10);
            $last = hash_hmac('md5', $rand, $key);
            // ---    1       1      1         1           3             3           4              1       1   ---
            return $rand[0] . $h . $rand[1] . $m . substr($rand, 2, 3) . $y . substr($last, 5, 4) . $d . $last[0] . $append;
        }
        else {
            $rand = self::random(16);
            $rand2 = substr(hash_hmac('md5', self::random(16), $key), 8, 16);
            return $rand . $rand2 . $append;
        }
    }

    /**
     * --- 获取 IP （非安全 IP）---
     * @return string
     */
    public static function ip(): string {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        else {
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
    }

    /** @var string HTTP_X_FORWARDED_FOR */
    public const REAL_IP_X = 'HTTP_X_FORWARDED_FOR';
    /** @var string HTTP_CF_CONNECTING_IP */
    public const REAL_IP_CF = 'HTTP_CF_CONNECTING_IP';
    /**
     * --- 获取直连 IP（安全 IP） ---
     * @param string $name 输入安全的 header
     * @return string
     */
    public static function realIP($name = ''): string {
        if (($name !== '') && isset($_SERVER[$name])) {
            return $_SERVER[$name];
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }

}

