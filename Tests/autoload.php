<?php
spl_autoload_register(function ($className) {

    if ('UnitTest' == substr($className, 0, 8)) {
        $className = preg_replace('~^UnitTest~', DS . 'Tests', $className);
        $fileName = ROOT_PATH . str_replace('\\', DS, $className) . '.php';

    } else {
        return false;
    }

    if (is_readable($fileName)) {
        require $fileName;
        return true;
    } else {
        return false;
    }

});