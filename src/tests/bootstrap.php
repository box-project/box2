<?php

/** @noinspection PhpIncludeInspection */
$loader = require __DIR__ .'/../vendors/autoload.php';
$loader->add(null, __DIR__);

define('BOX_PATH', realpath(__DIR__) . '/../..');

org\bovigo\vfs\vfsStreamWrapper::register();
