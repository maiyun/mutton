<?php
/**
 * For Captcha 1.1.7
 * Url: https://github.com/Gregwar/Captcha
 * User: JianSuoQiYue
 * Date: 2018-7-4 09:37
 * Last: 2019-1-29 16:10:50
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

    /** @var $_link CaptchaBuilder */
    private $_link;

    public static function get(int $width, int $height, int $len = 4): Captcha {
        $captcha = new Captcha($width, $height, $len);
        return $captcha;
    }

    public function __construct(int $width, int $height, int $len = 4) {
        $phrase = new PhraseBuilder($len, 'ACEFGHJKLMNPRSTWXY34567');
        $this->_link = new CaptchaBuilder(NULL, $phrase);
        $this->_link->build($width, $height);
    }

    // --- 直接输出 ---
    public function output(int $quality = 70): string {
        header('Content-type: image/jpeg');
        $this->_link->output($quality);
        return $this->_link->getPhrase();
    }

    // --- 获取 base64 ---
    public function getBase64(int $quality = 70): string {
        $old = ob_get_clean();
        ob_start();
        $this->_link->output($quality);
        $r = ob_get_clean();
        $str = 'data:image/jpg;base64,'.base64_encode($r);
        if ($old !== false) {
            ob_start();
            echo $old;
        }
        return $str;
    }

    /**
     * 获取随机码
     * @return string
     */
    public function getPhrase(): string {
        return $this->_link->getPhrase();
    }

}

