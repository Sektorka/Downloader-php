<?php

use includes\Manager;
use JetBrains\PhpStorm\NoReturn;

spl_autoload_register(callback: function ($class) {
    @include $class . CLASSES_EXT;
});

require_once "config.php";

chdir(dirname($argv[0]));

#[NoReturn] function printException(Exception $e) {
    exit(sprintf("Error: %s\r\nCode: %d\r\nFile: %s\r\nLine: %d",
        $e->getMessage(),
        $e->getCode(),
        $e->getFile(),
        $e->getLine()
    ));
}

try {
    Manager::initialize($argv);
    $manager = Manager::getInstance();
    $manager->startSearch(true);
}
catch(Exception $e){
    printException($e);
}