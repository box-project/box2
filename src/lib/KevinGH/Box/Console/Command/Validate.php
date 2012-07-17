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

    use KevinGH\Box\Console\Exception\JSONValidationException,
        Seld\JsonLint\ParsingException,
        Symfony\Component\Console\Command\Command,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Input\InputOption,
        Symfony\Component\Console\Output\OutputInterface;

    /**
     * Validates the configuration file.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Validate extends Command
    {
        /** {@inheritDoc} */
        public function configure()
        {
            $this->setName('validate')
                 ->setDescription('Validates the configuration file.');

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
            $config = $this->getHelper('config');

            try
            {
                $config->load($config->find($input->getOption('config')));
            }

            catch (JSONValidationException $exception)
            {
                $output->writeln("<error>The configuration file is not valid.</error>\n");

                foreach ($exception->getErrors() as $error)
                {
                    $output->writeln("    - $error");
                }

                return 1;
            }

            catch (ParsingException $exception)
            {
                $output->writeln("<error>The configuration file is not valid.</error>\n");

                throw $exception;
            }

            $output->writeln('<info>The configuration file is valid.</info>');
        }
    }