<?php
declare(strict_types = 1);

namespace mod;

class TestData extends Mod {

    protected static $_table = 'test_data';
    protected static $_primary = 'id';

    public $id, $test_id, $content, $time_add;

}

