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

    use KevinGH\Box\Console\Command,
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
        }

        /** {@inheritDoc} */
        public function getDefaultCommands()
        {
            $commands = parent::getDefaultCommands();

            $commands[] = new Command\Create;

            return $commands;
        }

        /** {@inheritDoc} */
        public function getDefaultHelperSet()
        {
            $helperSet = parent::getDefaultHelperSet();

            $helperSet->set(new Helper\Config);

            return $helperSet;
        }
    }