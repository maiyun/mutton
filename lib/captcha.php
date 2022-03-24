<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * CONF - {"ver":"0.3","folder":true} - END
 * Date: 2018-7-4 09:37
 * Last: 2019-1-29 16:10:50, 2020-3-11 23:14:45, 2022-3-24 13:47:03
 */
declare(strict_types = 1);

namespace lib;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

require LIB_PATH . 'captcha/CaptchaBuilderInterface.php';
require LIB_PATH . 'captcha/CaptchaBuilder.php';
require LIB_PATH . 'captcha/PhraseBuilderInterface.php';
require LIB_PATH . 'captcha/PhraseBuilder.php';

class Captcha {

    /** @var $_link CaptchaBuilder */
    private $_link;

    /**
     * --- 获取验证码对象 ---
     * @param int $width
     * @param int $height
     * @param int $len
     * @return Captcha
     */
    public static function get(int $width, int $height, int $len = 4): Captcha {
        $captcha = new Captcha($width, $height, $len);
        return $captcha;
    }

    public function __construct(int $width, int $height, int $len = 4) {
        $phrase = new PhraseBuilder($len, 'ACEFGHJKLMNPRSTWXY34567');
        $this->_link = new CaptchaBuilder(null, $phrase);
        $this->_link->build($width, $height);
    }

    /**
     * --- 获取图片 Buffer ---
     * @param int $quality 图片质量
     * @return string
     */
    public function getBuffer(int $quality = 70): string {
        $old = ob_get_clean();
        ob_start();
        header('content-type: image/jpeg');
        $this->_link->output($quality);
        $r = ob_get_clean();
        if ($old !== false) {
            ob_start();
            echo $old;
        }
        return $r;
    }

    /**
     * --- 获取 base64 格式图片 ---
     * @param int $quality 图片质量
     * @return string
     */
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
     * --- 获取当前随机码 ---
     * @return string
     */
    public function getPhrase(): string {
        return $this->_link->getPhrase();
    }

}

