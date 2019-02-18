<?php
/**
 * For phpseclib 2.0.14
 * Url: https://github.com/phpseclib/phpseclib
 * User: JianSuoQiYue
 * Date: 2018-7-29 13:25:44
 * Last: 2019-2-18 21:00:06
 */
declare(strict_types = 1);

namespace lib;

use lib\Comm\Sftp;
use lib\Comm\Ssh2;

require ETC_PATH.'comm.php';

spl_autoload_register(function (string $name) {
    if (substr($name, 0, 9) === 'phpseclib') {
        $cn = str_replace('\\', '/', $name);
        require LIB_PATH . 'Comm/phpseclib/' . $cn . '.php';
    }
}, true);

class Comm {

    /**
     * @param string $name 如 ssh, sftp
     * @param array|null $opt 配置文件
     * @return Ssh2|Sftp
     * @throws \Exception
     */
    public static function get(string $name, ?array $opt = []) {
        $name = strtolower($name);
        switch ($name) {
            case 'ssh':
            case 'ssh2':
                $ssh2 = new Ssh2();
                $ssh2->connect($opt);
                return $ssh2;
            case 'sftp':
                $sftp = new Sftp();
                $sftp->connect($opt);
                return $sftp;
        }
    }

    /**
     * --- 将文件权限码转换为八进位字符串方式显示 ---
     * @param int $num
     * @return string
     */
    public static function modeConvert(int $num): string {
        return substr(decoct($num), -4);
        // return '0' . ($num >> 6 & 7) . ($num >> 3 & 7) . ($num & 7);
    }

}

