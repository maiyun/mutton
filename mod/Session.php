<?php
declare(strict_types = 1);

namespace mod;

use sys\Ctr;

class Session extends Mod {

    protected static $_table = 'session';
    protected static $_primary = 'id';
    protected static $_key = 'token';

    public $id, $token, $data, $time_update, $time_add;

    protected function _keyGenerator(): string {
        return 'test_' . rand(0, 3);
        // return Ctr::_getRandom(16, Ctr::RANDOM_LUN);
    }

}

