<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2022-08-27 02:13:04
 * Last: 2022-08-27 02:13:08, 2022-09-02 13:11:07, 2023-4-10 17:56:12, 2023-12-27 15:50:38
 */
declare(strict_types = 1);

namespace lib;

class Core {

    /**
     * --- 设置 cookie ---
     * @param string $name 名
     * @param string $value 值
     * @param array $opt 选项 ttl, path, domain, ssl, httponly, samesite
     */
    public static function setCookie(string $name, string $value, array $opt = []): void {
        $ttl = !isset($opt['ttl']) ? 0 : $opt['ttl'];
        $opt = [
            'expires' => time() + $ttl,
            'path' => isset($opt['path']) ? $opt['path'] : '/',
            'domain' => isset($opt['domain']) ? $opt['domain'] : '',
            'secure' => isset($opt['ssl']) ? $opt['ssl'] : true,
            'httponly' => isset($opt['httponly']) ? $opt['httponly'] : true
        ];
        if (isset($opt['samesite'])) {
            $opt['samesite'] = $opt['samesite'];
        }
        setcookie($name, $value, $opt);
    }

    /**
     * --- 生成范围内的随机数，带小数点 ---
     * @param float $min >= 最小值
     * @param float $max <= 最大值
     * @param int $prec 保留几位小数
     * @return float
     */
    public static function rand(float $min, float $max, int $prec = 0): float {
        if ($prec < 0) {
            $prec = 0;
        }
        $p = pow(10, $prec);
        return rand((int)($min * $p), (int)($max * $p)) / $p;
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
    const RANDOM_LUNS = self::RANDOM_LUN . '()`~!@#$%^&*-+=_|{}[]:;\'<>,.?/]"';

    /**
     * --- 生成随机字符串 ---
     * @param int $length 长度
     * @param string $source 采样值
     * @param string $block 排除的字符
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

    const CONVERT62_CHAR = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * --- 将 10 进制转换为 62 进制 ---
     * @param int|string $n 10 进制数字最大 9223372036854775807
     */
    public static function convert62(int|string $n) {
        if (!is_string($n)) {
            $n = (string)$n;
        }
        $res = '';
        while ($n > 0) {
            $res = self::CONVERT62_CHAR[bcmod($n, '62', 0)] . $res;
            $n = bcdiv($n, '62', 0);
        }
        return $res;
    }

    /**
     * --- 将 62 进制转换为 10 进制 ---
     * @param string $n 62 进制数字最大 aZl8N0y58M7
     */
    public static function unconvert62(string $n): string {
        $res = '0';
        $nl = strlen($n);
        for ($i = 1; $i <= $nl; ++$i) {
            $res = bcadd($res, bcmul((string)strpos(self::CONVERT62_CHAR, $n[$i - 1]), bcpow('62', (string)($nl - $i), 0), 0), 0);
        }
        return $res;
    }
    
    /**
     * --- 去除 html 的空白符、换行以及注释 ---
     * @param string $text 要纯净的字符串
     */
    public static function purify(string $text): string {
        $text = '>' . $text . '<';
        $keepScripts = [];
        $keepPres = [];
        $nums = -1;
        $nump = -1;
        $text = preg_replace('/<!--([\s\S]*?)-->/', '', $text);
        $text = preg_replace_callback('/<script[\s\S]+?<\/script>/', function ($matches) use (&$keepScripts) {
            $keepScripts[] = $matches[0];
            return '[SCRIPT]';
        }, $text);
        $text = preg_replace_callback('/<pre[\s\S]+?<\/pre>/', function ($matches) use (&$keepPres) {
            $keepPres[] = $matches[0];
            return '[PRE]';
        }, $text);
        $text = preg_replace_callback('/>([\s\S]*?)</', function ($matches) {
            $t1 = preg_replace('/\t|\r\n| {2}/', '', $matches[1]);
            $t1 = preg_replace('/\n|\r/', '', $t1);
            return '>' . $t1 . '<';
        }, $text);
        $text = preg_replace_callback('/\[SCRIPT\]/', function() use ($keepScripts, &$nums) {
            ++$nums;
            return $keepScripts[$nums];
        }, $text);
        $text = preg_replace_callback('/\[PRE\]/', function() use ($keepPres, &$nump) {
            ++$nump;
            return $keepPres[$nump];
        }, $text);
        return substr($text, 1, -1);
    }

    /**
     * --- 获取 MUID ---
     * @param $opt len: 8 - 32, 默认 8; bin: 是否含有大小写, 默认 true; key: 多样性混合, 默认空; insert: 插入指定字符, 最好不超过 2 字符，默认空，num: 是否含有数字，默认 true
     * @return string
     */
    public static function muid($opt = []): string {
        $len = isset($opt['len']) ? $opt['len'] : 8;
        $bin = isset($opt['bin']) ? $opt['bin'] : true;
        $key = isset($opt['key']) ? $opt['key'] : '';
        $insert = isset($opt['insert']) ? $opt['insert'] : '';
        $num = isset($opt['num']) ? $opt['num'] : true;
        $ilen = strlen($insert);

        $char = hash_hmac('sha1', (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') .
        (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') .
        (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '') .
        (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') .
        (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '') .
        (isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : '') . 'muid' . $key . rand(0, 1000000000), 'muid');
        if (!$char) {
            return '';
        }

        // --- 生成随机数 ---
        $over = self::random($len - 1 - $ilen, $bin ? ($num ? self::RANDOM_LUN : self::RANDOM_LU) : ($num ? self::RANDOM_LN : self::RANDOM_L)) . $char[20];
        return $over[0] . $insert . substr($over, 1);
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

