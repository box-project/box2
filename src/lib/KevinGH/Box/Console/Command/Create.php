<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Command;

    use InvalidArgumentException,
        KevinGH\Box\Box,
        Phar,
        RuntimeException,
        Symfony\Component\Console\Command\Command,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Input\InputOption,
        Symfony\Component\Console\Output\OutputInterface;

    /**
     * The command that creates the PHAR.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Create extends Command
    {
        /**
         * The verbosity level.
         *
         * @type boolean
         */
        private $verbose = false;

        /** {@inheritDoc} */
        public function configure()
        {
            $this->setName('create')
                 ->setDescription('Creates a new PHAR.');

            $this->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'The configuration file path.'
            );
        }

        /** {@inheritDoc} */
        public function execute(InputInterface $input, OutputInterface $output)
        {
            $this->verbose = (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity());

            $config = $this->getHelper('config');

            $config->load($config->find($input->getOption('config')));

            if (true === $config['key-pass'])
            {
                $dialog = $this->getHelper('dialog');

                if ('' == ($config['key-pass'] = trim($dialog->ask($output, 'Private key password: '))))
                {
                    throw new InvalidArgumentException('Your private key password is required for signing.');
                }
            }

            $box = $this->start();

            if ($this->verbose)
            {
                $output->writeln('Adding files...');
            }

            foreach ($config->getFiles() as $file)
            {
                $relative = $config->relativeOf($file);

                if ($this->verbose)
                {
                    $output->writeln("    - $relative");
                }

                $box->importFile($relative, $file);
            }

            $this->end($box);
        }

        /**
         * Ends by finishing the PHAR.
         *
         * @throws InvalidArgumentException If a file does not exist.
         * @throws RuntimeException If a file could not be read.
         * @param Box $box The Box instance.
         */
        protected function end(Box $box)
        {
            $config = $this->getHelper('config');

            $cwd = $config->getCurrentDir();

            chdir($config['base-path']);

            if ($config['main'])
            {
                if (false === ($real = realpath($config['main'])))
                {
                    throw new InvalidArgumentException('The main file does not exist.');
                }

                $box->importFile($config->relativeOf($real), $real, true);
            }

            if (true === $config['stub'])
            {
                $box->setStub($box->createStub());
            }

            elseif ($config['stub'])
            {
                if (false === file_exists($config['stub']))
                {
                    throw new InvalidArgumentException('The stub file does not exist.');
                }

                if (false === ($stub = @ file_get_contents($config['stub'])))
                {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'The stub file could not be read: %s',
                        $error['message']
                    ));
                }

                $box->setStub($stub);
            }

            $box->stopBuffering();

            if ($config['key'])
            {
                $box->usePrivateKeyFile($config['key'], $config['key-pass']);
            }

            else
            {
                $box->setSignatureAlgorithm($config['algorithm']);
            }

            chdir($cwd);
        }

        /**
         * Starts a new PHAR.
         *
         * @return Box The Box instance.
         */
        protected function start()
        {
            $config = $this->getHelper('config');

            $box = new Box(
                $config['base-path'] . DIRECTORY_SEPARATOR . $config['output'],
                0,
                $config['alias']
            );

            if ($config['replacements'])
            {
                $box->setReplacements($config['replacements']);
            }

            $box->startBuffering();

            return $box;
        }
    }