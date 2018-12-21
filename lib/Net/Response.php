<?php
/**
 * User: JianSuoQiYue
 * Date: 2018-12-10 19:51:06
 * Last: 2018-12-11 21:26:26
 */
declare(strict_types = 1);

namespace lib\Net;

class Response {

    public $header = '';
    public $content = '';

    public $error = '';
    public $errNo = 0;
    public $errInfo = NULL;

    public function __construct(array $opt = []) {
        $this->header = isset($opt['header']) ? $opt['header'] : '';
        $this->content = isset($opt['content']) ? $opt['content'] : '';

        $this->error = isset($opt['error']) ? $opt['error'] : '';
        $this->errNo = isset($opt['errNo']) ? $opt['errNo'] : 0;
        $this->errInfo = isset($opt['errInfo']) ? $opt['errInfo'] : NULL;
    }

    public static function get(array $opt = []): Response {
        return new Response($opt);
    }

}

