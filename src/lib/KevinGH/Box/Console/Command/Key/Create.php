<?php

    /* This file is part of Box.
     *
     * (c) 2012 Kevin Herrera
     *
     * For the full copyright and license information, please
     * view the LICENSE file that was distributed with this
     * source code.
     */

    namespace KevinGH\Box\Console\Command\Key;

    use RuntimeException,
        Symfony\Component\Console\Command\Command,
        Symfony\Component\Console\Input\InputArgument,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Input\InputOption,
        Symfony\Component\Console\Output\OutputInterface;

    /**
     * Generates a new private key.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Create extends Command
    {
        /** {@inheritDoc} */
        public function configure()
        {
            $this->setName('key:create')
                 ->setDescription('Creates a new private key.');

            $this->addOption(
                'bits',
                'b',
                InputOption::VALUE_REQUIRED,
                'The number of bits to generate (default: 1024)'
            );

            $this->addOption(
                'out',
                'o',
                InputOption::VALUE_REQUIRED,
                'The output file. (default: private.key)'
            );

            $this->addOption(
                'passphrase',
                'p',
                InputOption::VALUE_REQUIRED,
                'The passphrased used instead of prompting.'
            );

            $this->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'The private key type. (default: rsa)'
            );
        }

        /** {@inheritDoc} */
        public function execute(InputInterface $input, OutputInterface $output)
        {
            $helper = $this->getHelper('openssl');

            if ($type = $input->getOption('type'))
            {
                $types = $helper->getKeyTypes();

                if (false === isset($types[$type]))
                {
                    $output->writeln(sprintf(
                        '<error>Key type not supported: %s</error>',
                        $type
                    ));

                    $output->writeln("\nSupported key types:");

                    foreach ($types as $type => $code)
                    {
                        $output->writeln("    - $type");
                    }

                    return 1;
                }
            }

            if ('' == ($passphrase = $input->getOption('passphrase')))
            {
                $dialog = $this->getHelper('dialog');

                $passphrase = $dialog->ask($output, 'New passphrase (blank for none): ') ?: null;
            }

            $this->getHelper('openssl')->createPrivateFile(
                $input->getOption('out') ?: 'private.key',
                $passphrase,
                $type,
                $input->getOption('bits')
            );
        }
    }