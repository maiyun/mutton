<?php
declare(strict_types = 1);

namespace mod;

class Test extends Mod {

    protected static $_table = 'test';
    protected static $_primary = 'id';

    public $id, $name, $time_add;

}

