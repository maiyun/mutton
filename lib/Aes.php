<?php
/**
 * User: JianSuoQiYue
 * Date: 2015/11/26 12:56
 * Last: 2018/06/11 12:19
 */
declare(strict_types = 1);

namespace M\lib {

	class Aes {

        public static function encrypt(string $original, string $key): string {

            return base64_encode(openssl_encrypt($original . '#', 'AES-256-ECB', $key));

        }

        // --- 返回空代表解密失败 ---
        public static function decrypt(string $encrypt, string $key): string {

            if ($rtn = openssl_decrypt(base64_decode($encrypt), 'AES-256-ECB', $key)) {
                if(strrpos($rtn, '#') !== false) {
                    // --- 解密后, 最后一位是定位符 #, 后面会有 AES 的填充, 都通通不要.
                    return substr($rtn, 0, strrpos($rtn, '#'));
                } else {
                    return '';
                }
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

}

