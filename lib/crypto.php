<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2015/11/26 12:56
 * Last: 2019-2-1 15:59:13, 2020-2-8 10:22:59, 2020-3-12 17:21:45
 */
declare(strict_types = 1);

namespace lib;

class Crypto {

    // --- AES 加/解密 ---

    const AES_256_ECB = 'AES-256-ECB';      // 如果未设置 iv，则默认这个
    const AES_256_CBC = 'AES-256-CBC';
    const AES_256_CFB = 'AES-256-CFB';      // 一般用这个，设置 $iv，自动就切换成了这个

    /**
     * --- AES 加密 ---
     * @param string $original 原始字符串
     * @param string $key 密钥 32 个英文字母和数字
     * @param string $iv 向量 16 个英文字母和数字
     * @param string $method 加密方法
     * @return string
     */
    public static function aesEncrypt(string $original, string $key, string $iv = '', string $method = self::AES_256_ECB): string {
        if ($iv !== '') {
            $method = $method === self::AES_256_ECB ? self::AES_256_CFB : $method;
        }
        if (strlen($key) < 32) {
            $key = hash_hmac('md5', $key, 'MaiyunSalt');
        }
        if ($rtn = @openssl_encrypt($original, $method, $key, OPENSSL_RAW_DATA, $iv)) {
            return base64_encode($rtn);
        } else {
            return '';
        }
    }

    /**
     * --- AES 解密 ---
     * @param string $encrypt 需解密的字符串
     * @param string $key 密钥 32 个英文字母和数字
     * @param string $iv 向量 16 个英文字母和数字
     * @param string $method 加密方法
     * @return string
     */
    public static function aesDecrypt(string $encrypt, string $key, string $iv = '', string $method = self::AES_256_ECB): string {
        if ($iv !== '') {
            $method = $method === self::AES_256_ECB ? self::AES_256_CFB : $method;
        }
        if (strlen($key) < 32) {
            $key = hash_hmac('md5', $key, 'MaiyunSalt');
        }
        if ($rtn = @openssl_decrypt(base64_decode($encrypt), $method, $key, OPENSSL_RAW_DATA, $iv)) {
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

