<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    Phar::mapPhar('default.phar');

    require 'phar://default.phar/bin/main.php';

    __HALT_COMPILER();