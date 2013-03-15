<?php

namespace KevinGH\Box;

use ErrorException;
use KevinGH\Box\Helper\ConfigurationHelper;
use Symfony\Component\Console\Application as Base;

/**
 * Automatically sets up the application.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Application extends Base
{
    /**
     * @override
     */
    public function __construct($name = 'Box', $version = '@git_tag@')
    {
        // convert errors to exceptions
        set_error_handler(function ($code, $message, $file, $line) {
            if (error_reporting() & $code) {
                throw new ErrorException($message, 0, $code, $file, $line);
            }
        });

        parent::__construct($name, $version);
    }

    /**
     * @override
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new ConfigurationHelper());

        return $helperSet;
    }
}