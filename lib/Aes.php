<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2015/11/26
 * Time: 12:56
 */

namespace C\lib {

	class Aes {

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

	}

}

