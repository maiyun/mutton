<?php
/**
 * Project: Mutton, User: JianSuoQiYue
 * Date: 2018-12-10 19:51:06
 * Last: 2018-12-11 21:26:26, 2020-2-26 12:01:25, 2020-4-11 22:57:04
 */
declare(strict_types = 1);

namespace lib\Net;

class Response {

    public $headers = [];
    /** @var string|null 返回的数据 */
    public $content = null;

    public $error = '';
    public $errno = 0;
    public $info = null;

    public function __construct(array $opt = []) {
        $this->headers = isset($opt['headers']) ? $opt['headers'] : [];
        $this->content = isset($opt['content']) ? $opt['content'] : '';

        $this->error = isset($opt['error']) ? $opt['error'] : '';
        $this->errno = isset($opt['errno']) ? $opt['errno'] : 0;
        $this->info = isset($opt['info']) ? $opt['info'] : null;
    }

}

