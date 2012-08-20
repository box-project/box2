#!/usr/bin/env php
<?php
Phar::mapPhar('example.phar');
require 'phar://example.phar/bin/example';
__HALT_COMPILER();