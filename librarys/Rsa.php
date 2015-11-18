<?php
/**
 * Chameleon 框架
 * User: 韩国帅
 * Github: https://github.com/yunbookf/Chameleon
 */

namespace Chameleon\Library;

class Rsa {

    /**
     * @param $original
     * @param string $public_key 文件地址或 key 的内容，以第一个字符为 @ 则为文件地址
     * @return bool|string
     */
    public function encrypt($original, $public_key = '@') {

        $encrypt = '';
        $public_key = $public_key == '@' ? '@'.RSA_PUBLIC_KEY : $public_key;
        $publicKey = substr($public_key, 0, 1) == '@' ? file_get_contents(substr($public_key, 1)) : $public_key;
        $publicKey = openssl_pkey_get_public($publicKey);
        while($original != '') {
            $originalLine = substr($original, 0, 117);
            $encryptLine = '';
            openssl_public_encrypt($originalLine, $encryptLine, $publicKey);
            $encrypt .= base64_encode($encryptLine) . '&';
            $original = substr($original, 117);
        }
        if($encrypt != '') return substr($encrypt, 0, -1);
        return false;

    }

    public function decrypt($encrypt, $private_key = '@') {

        $original = '';
        $private_key = $private_key == '@' ? '@'.RSA_PRIVATE_KEY : $private_key;
        $privateKey = substr($private_key, 0, 1) == '@' ? file_get_contents(substr($private_key, 1)) : $private_key;
        $privateKey = openssl_pkey_get_private($privateKey);
        $encrypt = explode('&', $encrypt);
        foreach($encrypt as $encryptLine) {
            $originalTmp = '';
            openssl_private_decrypt(base64_decode($encryptLine), $originalTmp, $privateKey);
            $original .= $originalTmp;
        }
        return $original == '' ? false : $original;

    }

}