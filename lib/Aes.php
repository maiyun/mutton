<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/11/26 12:56
 * Last: 2018-12-8 15:49:44
 */
declare(strict_types = 1);

namespace lib;

class Aes {

    const AES_256_ECB = 'AES-256-ECB';
    const AES_256_CBC = 'AES-256-CBC';
    const AES_256_CFB = 'AES-256-CFB';

    // --- 返回空代表加密失败 ---
    public static function encrypt(string $original, string $key, string $iv = '', string $method = 'AES-256-ECB'): string {
        if ($iv !== '') {
            $method = $method === 'AES-256-ECB' ? 'AES-256-CFB' : $method;
            $iv = substr(md5($iv), 8, 16);
        }
        if ($rtn = openssl_encrypt($original . '#', $method, $key, OPENSSL_RAW_DATA, $iv)) {
            return base64_encode($rtn);
        } else {
            return '';
        }
    }

    // --- 返回空代表解密失败 ---
    public static function decrypt(string $encrypt, string $key, string $iv = '', string $method = 'AES-256-ECB'): string {
        if ($iv !== '') {
            $method = $method === 'AES-256-ECB' ? 'AES-256-CFB' : $method;
            $iv = substr(md5($iv), 8, 16);
        }
        if ($rtn = openssl_decrypt(base64_decode($encrypt), $method, $key, OPENSSL_RAW_DATA, $iv)) {
            return $rtn;
        } else {
            return '';
        }
    }

    /* PHP 7 以下，已经废弃了，不过还是留着吧

    public static function encrypt($original, $key) {

        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $original . '#', MCRYPT_MODE_ECB));

    }

    public static function decrypt($encrypt, $key) {

        if ($rtn = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypt), MCRYPT_MODE_ECB)) {
            if(strrpos($rtn, '#') !== false) {
                // --- 解密后, 最后一位是定位符 #, 后面会有 AES 的填充, 都通通不要.
                return substr($rtn, 0, strrpos($rtn, '#'));
            } else {
                return false;
            }
        } else {
            return false;
        }

    }

    */

}

