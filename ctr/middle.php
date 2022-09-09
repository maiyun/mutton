<?php
declare(strict_types = 1);

namespace ctr;

use sys\Ctr;

class middle extends Ctr {

    public function onload() {
        if (PATH !== 'test/middle') {
            return true;
        }
        return '_action: ' . $this->_action . '<br><br>In fact, the middle method does not exist in the test controller, which uses Mutton\'s middle structure to preprocess requests.';
    }

}

