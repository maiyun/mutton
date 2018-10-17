<?php
/**
 * For Captcha 1.1.6
 * Url: https://github.com/Gregwar/Captcha
 * User: JianSuoQiYue
 * Date: 2018-7-4 09:37
 * Last: 2018-7-28 14:55:00
 */
declare(strict_types = 1);

namespace lib;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

require LIB_PATH . 'Captcha/src/Gregwar/Captcha/CaptchaBuilderInterface.php';
require LIB_PATH . 'Captcha/src/Gregwar/Captcha/CaptchaBuilder.php';
require LIB_PATH . 'Captcha/src/Gregwar/Captcha/PhraseBuilderInterface.php';
require LIB_PATH . 'Captcha/src/Gregwar/Captcha/PhraseBuilder.php';

class Captcha {

    public static function fastBuild(int $width, int $height, bool $base64 = false, int $len = 4): string {
        if (!$base64) {
            header('Content-type: image/jpeg');
        }
        $phrase = new PhraseBuilder($len, 'ABCEFGHJKLMNPRSTWXYZ23456789');
        $builder = new CaptchaBuilder(NULL, $phrase);
        $builder->build($width, $height);
        if ($base64) {
            $old = ob_get_clean();
            ob_start();
            $builder->output();
            $r = ob_get_clean();
            echo 'data:image/jpg;base64,'.base64_encode($r);
            if ($old !== false) {
                ob_start();
                echo $old;
            }
        } else {
            $builder->output();
        }
        return $builder->getPhrase();
    }

}

