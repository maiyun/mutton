<?php
declare(strict_types = 1);

namespace ctr;

use sys\Ctr;

class middle extends Ctr {

    public function _load() {
        if (URI !== 'test/middle') {
            return true;
        }
        return 'action: ' . $this->_action;
    }

}

