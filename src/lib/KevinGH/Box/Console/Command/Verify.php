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
        Symfony\Component\Console\Command\Command,
        Symfony\Component\Console\Input\InputArgument,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Output\OutputInterface,
        UnexpectedValueException;

    /**
     * Verifies the PHAR's signature.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Verify extends Command
    {
        /** {@inheritDoc} */
        public function configure()
        {
            $this->setName('verify')
                 ->setDescription('Verifies the PHAR.');

            $this->addArgument(
                'phar',
                InputArgument::REQUIRED,
                'The PHAR file path.'
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

            try
            {
                // Phar class verifies in constructor
                $phar = new Phar($file);

                $signature = $phar->getSignature();

                if ('OpenSSL' === $signature['hash_type'])
                {
                    $output->writeln('<info>The PHAR is verified and signed.</info>');
                }

                else
                {
                    $output->writeln('<comment>The PHAR is verified but not signed.</comment>');
                }
            }

            catch (UnexpectedValueException $exception)
            {
                $output->writeln('<error>The PHAR failed verification.</error>');

                if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity())
                {
                    throw $exception;
                }

                return 1;
            }
        }
    }