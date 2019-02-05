<?php
declare(strict_types = 1);

namespace ctr;

use sys\Ctr;

class main extends Ctr {

    public function main() {
        return '<a href="' . HTTP_BASE . 'test/main">Hello world! Click here to visit demo.</a>';
    }

}

