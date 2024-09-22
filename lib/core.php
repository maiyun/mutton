<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2022-08-27 02:13:04
 * Last: 2022-08-27 02:13:08, 2022-09-02 13:11:07, 2023-4-10 17:56:12, 2023-12-27 15:50:38, 2024-8-15 18:36:11
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
     * --- 判断一个对象是否符合示例组，返回空字符串代表校验通过，返回：应该的类型:位置:传入的类型 ---
     * @param $val 对象
     * @param $type 示例组
     * @param $tree 当前树，无需传入
     */
    public static function checkType($val, $type, string $tree = 'root'): string {
        /** --- 要校验的对象 --- */
        $vtype = strtolower(gettype($val));
        if (is_array($type) && isset($type[0])) {
            // --- 数组的话 ---
            if (!is_array($val) || !isset($val[0])) {
                return 'array:' . $tree . ':' . (!is_array($val) ? $vtype : 'object');
            }
            $length = count($val);
            for ($i = 0; $i < $length; ++$i) {
                $res = self::checkType($val[$i], $type[0], $tree . '.' . $i);
                if ($res) {
                    return $res;
                }
            }
            return '';
        }
        /** --- 要符合的类型 --- */
        $ttype = strtolower(gettype($type));
        if ($ttype === 'string' && isset($type[0]) && $type[0] === '/') {
            // --- 正则 ---
            if ($vtype !== 'string') {
                return 'regexp:' . $tree . ':' . $vtype;
            }
            return preg_match($type, $val) ? '' : 'regexp:' . $tree . ':' . $vtype;
        }
        if ($val === null) {
            return $ttype . ':' . $tree . ':null';
        }
        if ($ttype === 'string') {
            if ($vtype !== 'string') {
                return 'string:' + $tree + ':' + $vtype;
            }
            if ($type) {
                return $val ? '' : 'require:' . $tree . ':' . $vtype;
            }
            return '';
        }
        if (is_array($type) && !isset($type[0])) {
            if (!is_array($val) || isset($val[0])) {
                return 'object:' . $tree . ':' . (!is_array($val) ? $vtype : 'array');
            }
            foreach ($type as $key => $typeKey) {
                if (!isset($val[$key])) {
                    $val[$key] = null;
                }
                $res = self::checkType($val[$key], $typeKey, $tree . '.' . $key);
                if ($res) {
                    return $res;
                }
            }
            return '';
        }
        return $vtype === $ttype ? '' : $ttype . ':' . $tree . ':' . $vtype;
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

    /**
     * --- 获取日志内容为一个数组 ---
     * @param array $opt path(2024/08/01/22), fend(可选，-error), search(可选), offset(可选，默认0), limit(可选，默认100)
     */
    public static function getLog(array $opt): array | null | false {
        $path = LOG_PATH . $opt['path'] . (isset($opt['fend']) ? $opt['fend'] : '') . '.csv';
        if (!is_file($path)) {
            return null;
        }
        /** --- 剩余 limit --- */
        $limit = isset($opt['limit']) ? $opt['limit'] : 100;
        /** --- 剩余 offset --- */
        $offset = isset($opt['offset']) ? $opt['offset'] : 0;

        $list = [];
        /** --- 当前行号 --- */
        $line = 0;
        /** --- 当前行数据 --- */
        $packet = '';
        $fh = fopen($path, 'r');
        if (!$fh) {
            return false;
        }
        while (!feof($fh)) {
            $buf = fread($fh, 32768);
            if ($buf === false) {
                return false;
            }
            while (true) {
                // --- 分包 ---
                $index = strpos($buf, "\n");
                if ($index === false) {
                    // --- 本次包还没有结束 ---
                    $packet .= $buf;
                    break;
                }
                // --- 本次行结束了 ---
                if ($limit === 0) {
                    break;
                }
                $packet .= substr($buf, 0, $index);
                $buf = substr($buf, $index + 1);
                ++$line;
                // --- 先执行下本次完成的 ---
                if ($line > 1) {
                    if ($offset === 0) {
                        $result = [];
                        $currentField = '';
                        $inQuotes = false;
                        $packetLength = strlen($packet);
                        for ($i = 0; $i < $packetLength; ++$i) {
                            $char = $packet[$i];
                            if ($char === '"') {
                                if ($inQuotes && isset($packet[$i + 1]) && $packet[$i + 1] === '"') {
                                    $currentField .= '"';
                                    ++$i;
                                }
                                else {
                                    $inQuotes = !$inQuotes;
                                }
                            }
                            else if ($char === ',' && !$inQuotes) {
                                $result[] = $currentField;
                                $currentField = '';
                            }
                            else {
                                $currentField .= $char;
                            }
                        }
                        $result[] = $currentField;
                        $list[] = $result;
                        --$limit;
                    }
                    else {
                        --$offset;
                    }
                }
                // --- 处理结束 ---
                $packet = '';
                // --- 看看还有没有后面的粘连包 ---
                if (!strlen($buf)) {
                    // --- 没粘连包 ---
                    break;
                }
                // --- 有粘连包 ---
            }
        }
        fclose($fh);
        return $list;
    }

}

