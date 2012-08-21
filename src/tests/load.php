<?php

define('BOX_PATH', dirname(dirname(__DIR__)));

call_user_func(function () {
    $loader = require __DIR__ . '/../vendors/autoload.php';

    $loader->add(null, __DIR__);
});

