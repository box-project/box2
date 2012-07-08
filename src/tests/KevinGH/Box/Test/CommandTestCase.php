<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Test;

    use KevinGH\Box\Console\Application,
        Symfony\Component\Console\Tester\CommandTester;

    /**
     * A test case for Box commands.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class CommandTestCase extends TestCase
    {
        /**
         * The command being tested.
         *
         * @type string
         */
        const COMMAND = 'COMMAND_NAME';

        /**
         * The command line application.
         *
         * @type Application
         */
        protected $app;

        /**
         * The command.
         *
         * @type Command
         */
        protected $command;

        /**
         * The directory for the app.
         *
         * @type string
         */
        protected $dir;

        /**
         * The command tester.
         *
         * @type CommandTester
         */
        protected $tester;

        /**
         * Sets up the command.
         */
        protected function setUp()
        {
            parent::setUp();

            $this->app = new Application;

            $this->command = $this->app->find(static::COMMAND);

            $this->dir = $this->dir();

            $this->tester = new CommandTester($this->command);
        }

        /**
         * Prepares a new application.
         */
        public function prepareApp($pass = null)
        {
            mkdir($this->dir . '/src/lib', 0755, true);
            mkdir($this->dir . '/bin');

            copy(RESOURCES . 'app/bin/main.php', $this->dir . '/bin/main.php');
            copy(RESOURCES . 'app/src/stub.php', $this->dir . '/src/stub.php');
            copy(RESOURCES . 'app/src/lib/class.php', $this->dir . '/src/lib/class.php');

            file_put_contents($this->dir . '/test.pem', $this->createPrivateKey($pass));

            $this->command('git init', $this->dir);
            $this->command('git add .', $this->dir);
            $this->command('git commit -a --author="Test <test@test.com>" -m "Adding current work."', $this->dir);
            $this->command('git tag v1.0-ALPHA1', $this->dir);
        }

        /**
         * Sets the configuration settings.
         *
         * @param array $settings The settings.
         * @return string The config file path.
         */
        public function setConfig(array $settings)
        {
            $file = $this->dir . '/config.json';

            file_put_contents($file, utf8_encode(json_encode($settings)));

            return $file;
        }
    }