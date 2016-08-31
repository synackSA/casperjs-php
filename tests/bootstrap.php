<?php
include_once __DIR__.'/../../../../vendor/autoload.php';

$classLoader = new \Composer\Autoload\ClassLoader();
$classLoader->addPsr4("synacksa\\casperjsphp\\", __DIR__, true);
$classLoader->register();
