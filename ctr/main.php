<?php
declare(strict_types = 1);

namespace ctr;

use sys\Ctr;

class main extends Ctr {

    public function index() {
        return '<a href="' . HTTP_BASE . 'test">Hello world! Click here to visit demo.</a>';
    }

}

