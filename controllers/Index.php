<?php

/**
 * Class Index
 */

namespace Chameleon\Controller;

class Index extends \Main {

    function __construct() {

        parent::__construct();

    }

    function index() {

        $this->loadView('index');

    }

}

