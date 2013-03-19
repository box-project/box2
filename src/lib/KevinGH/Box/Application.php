<?php

namespace KevinGH\Box;

use ErrorException;
use KevinGH\Box\Command;
use KevinGH\Box\Helper;
use Symfony\Component\Console\Application as Base;

/**
 * Sets up the application.
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
    protected function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new Command\Key\Create();
        $commands[] = new Command\Key\Extract();
        $commands[] = new Command\Build();

        return $commands;
    }

    /**
     * @override
     */
    protected function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();
        $helperSet->set(new Helper\ConfigurationHelper());
        $helperSet->set(new Helper\PhpSecLibHelper());

        return $helperSet;
    }
}