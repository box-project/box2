<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Console;

use ErrorException;
use KevinGH\Amend;
use KevinGH\Box\Console\Command;
use KevinGH\Box\Console\Helper;
use Symfony\Component\Console\Application as _Application;

/**
 * The Box application class.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Application extends _Application
{
    /** {@inheritDoc} */
    public function __construct($name = 'Box', $version = '@git_version@')
    {
        parent::__construct($name, $version);

        set_error_handler(function($code, $message, $file, $line)
        {
            if (error_reporting() & $code) {
                throw new ErrorException($message, 0, $code, $file, $line);
            }
        });
    }

    /** {@inheritDoc} */
    public function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new Command\Create;
        $commands[] = new Command\Edit\Add;
        $commands[] = new Command\Edit\Remove;
        $commands[] = new Command\Extract;
        $commands[] = new Command\Info;
        $commands[] = new Command\Validate;
        $commands[] = new Command\Verify;

        if (false === strpos($this->getVersion(), 'git_version')) {
            $commands[] = new Command\Update;
        }

        if (extension_loaded('openssl')) {
            $commands[] = new Command\Key\Create;
            $commands[] = new Command\Key\Extract;
        }

        return $commands;
    }

    /** {@inheritDoc} */
    public function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        $helperSet->set(new Helper\Config);
        $helperSet->set(new Helper\JSON);

        if (false === strpos($this->getVersion(), 'git_version')) {
            $helperSet->set(new Amend\Helper);
        }

        if (extension_loaded('openssl')) {
            $helperSet->set(new Helper\OpenSSL);
        }

        return $helperSet;
    }
}