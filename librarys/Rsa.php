<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2015/11/18
 * Time: 11:12
 */

namespace Chameleon\Library;

class Rsa {

    public function encrypt($original) {

        $encrypt = '';
        $publicKey = openssl_pkey_get_public(file_get_contents('rsa_public_key.pem'));
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

    public function decrypt($encrypt) {

        $original = '';
        $privateKey = openssl_pkey_get_private(file_get_contents('rsa_private_key.pem'));
        $encrypt = explode('&', $encrypt);
        foreach($encrypt as $encryptLine) {
            $originalTmp = '';
            openssl_private_decrypt(base64_decode($encryptLine), $originalTmp, $privateKey);
            $original .= $originalTmp;
        }
        return $original == '' ? false : $original;

    }

}