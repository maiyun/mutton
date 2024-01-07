<?php
declare(strict_types = 1);

namespace ctr;

use sys\Ctr;

class Middle extends Ctr {

    public function onLoad() {
        if (PATH !== 'test/middle') {
            return true;
        }
        return '_action: ' . $this->_action . "<br><br>In fact, the middle method does not exist in the test controller, which uses Mutton's middle structure to preprocess requests.";
    }

    public function onUnload($rtn) {
        if (!is_array($rtn)) {
            return $rtn;
        }
        if ($rtn[0] !== -101) {
            return $rtn;
        }
        $rtn['middle'] = 'unload';
        return $rtn;
    }

}

