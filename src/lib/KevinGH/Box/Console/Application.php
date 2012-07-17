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

    use ErrorException,
        KevinGH\Box\Console\Command,
        KevinGH\Box\Console\Helper,
        Symfony\Component\Console\Application as _Application;

    /**
     * The Box application class.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Application extends _Application
    {
        /**
         * The application version.
         *
         * @type string
         */
        const VERSION = '@git_version@';

        /** {@inheritDoc} */
        public function __construct($name = 'Box', $version = self::VERSION)
        {
            parent::__construct($name, $version);

            set_error_handler(function($code, $message, $file, $line)
            {
                if (error_reporting() & $code)
                {
                    throw new ErrorException($message, 0, $code, $file, $line);
                }
            });
        }

        /** {@inheritDoc} */
        public function getDefaultCommands()
        {
            $commands = parent::getDefaultCommands();

            $commands[] = new Command\Create;
            $commands[] = new Command\Extract;
            $commands[] = new Command\Update;
            $commands[] = new Command\Validate;
            $commands[] = new Command\Verify;

            return $commands;
        }

        /** {@inheritDoc} */
        public function getDefaultHelperSet()
        {
            $helperSet = parent::getDefaultHelperSet();

            $helperSet->set(new Helper\Config);
            $helperSet->set(new Helper\JSON);

            return $helperSet;
        }
    }