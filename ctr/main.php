<?php
declare(strict_types = 1);

namespace ctr;

use sys\Ctr;

class Main extends Ctr {

    public function index() {
        return '<a href="' . URL_BASE . 'test">Hello world! Click here to visit demo.</a>';
    }

}

