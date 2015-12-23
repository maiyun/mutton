<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2015/11/26
 * Time: 12:56
 */

namespace Chameleon\Library;

class Aes {

	public function encrypt($original, $key) {

		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $original, MCRYPT_MODE_ECB));

	}

	public function decrypt($encrypt, $key) {

		if($rtn = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypt), MCRYPT_MODE_ECB)) {
			return substr($rtn, 0, strrpos($rtn, '}') + 1);
		} else
			return false;

	}

}

