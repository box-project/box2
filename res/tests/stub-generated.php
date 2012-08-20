#!/usr/bin/env php
<?php

/**
 * Genereated by Box: http://github.com/kherge/Box
 */

Phar::mapPhar('$alias');

Phar::interceptFileFuncs();

require 'phar://$alias/$main';

__HALT_COMPILER();