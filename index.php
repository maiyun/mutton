<?php

require('confs/main.php');

$c = NULL;

if($_CONTROLFOLDER != '') {

    $ac = ucfirst($_ACTION);
    if(is_file(CONTROL_PATH.$_CONTROLFOLDER.'Controller.php')) {
        require(CONTROL_PATH . $_CONTROLFOLDER . 'Controller.php');
        if(is_file(CONTROL_PATH . $_CONTROLFOLDER . $_CONTROLLER . '.php'))
            require(CONTROL_PATH . $_CONTROLFOLDER . $_CONTROLLER . '.php');
        else
            header('Location: ' . SITE_PATH . '?controller-not-found');
    } else
        header('Location: ' . SITE_PATH . '?controller-head-not-found');

} else {

    require(CONTROL_PATH.$_CONTROLLER.'.php');

}

$c = new $_CONTROLLER();

if(method_exists($c, '__remap')) {
    $c->__remap();
} else {
    if (method_exists($c, $_ACTION)) $c->$_ACTION();
    else header('Location: ' . SITE_PATH . '?action-not-found-'.$_ACTION);
}

