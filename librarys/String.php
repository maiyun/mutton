<?php
/**
 * Created by PhpStorm.
 * User: Admin2
 * Date: 2015/5/7
 * Time: 13:50
 */

namespace Chameleon\Library;

class String {

    public function random($length = 8, $s = ['L', 'N']) {
        static $a = ['U' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'L' => 'abcdefghijklmnopqrstuvwxyz', 'N' => '0123456789'];
        $source = '';
        foreach ($s as $i) {
            switch ($i) {
                case 'U': case 'L': case 'N':
                    $source .= $a[$i];
                    break;
                default:
                    $source .= $i;
            }
        }
        $sourceLength = strlen($source);
        $temp = '';
        for ($i = $length; $i; $i--)
            $temp .= $source[rand(0, $sourceLength - 1)];
        return $temp;
    }

    public function isPhone($p) {

        if(preg_match('/1[3458]{1}\d{9}$/' ,$p)) return true;
        else return false;

    }

}