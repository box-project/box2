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
        Phar,
        PharException,
        Symfony\Component\Console\Command\Command,
        Symfony\Component\Console\Input\InputArgument,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Input\InputOption,
        Symfony\Component\Console\Output\OutputInterface,
        UnexpectedValueException;

    /**
     * Extracts the PHAR to a directory.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Extract extends Command
    {
        /** {@inheritDoc} */
        public function configure()
        {
            $this->setName('extract')
                 ->setDescription('Extracts the PHAR to a directory.');

            $this->addArgument(
                'phar',
                InputArgument::REQUIRED,
                'The PHAR file path.'
            );

            $this->addOption(
                'out',
                'o',
                InputOption::VALUE_REQUIRED,
                'The output directory path.'
            );

            $this->addOption(
                'want',
                'w',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'The file or directory wanted from the PHAR.'
            );
        }

        /** {@inheritDoc} */
        public function execute(InputInterface $input, OutputInterface $output)
        {
            $file = $input->getArgument('phar');

            if (false === file_exists($file))
            {
                throw new InvalidArgumentException('The PHAR does not exist.');
            }

            if (null === ($out = $input->getOption('out')))
            {
                $out = realpath($file) . '-contents';
            }

            try
            {
                $phar = new Phar($file);

                if ($want = $input->getOption('want'))
                {
                    foreach ($want as $wanted)
                    {
                        $phar->extractTo($out, $wanted, true);
                    }
                }

                else
                {
                    $phar->extractTo($out, null, true);
                }
            }

            catch (PharException $exception)
            {
                if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity())
                {
                    throw $exception;
                }

                else
                {
                    $output->writeln(sprintf(
                        '<error>The PHAR could not be extracted: %s',
                        $exception->getMessage()
                    ));
                }

                return 1;
            }

            catch (UnexpectedValueException $exception)
            {
                $output->writeln('<error>The PHAR could not be opened.</error>');

                if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity())
                {
                    throw $exception;
                }

                return 1;
            }
        }
    }