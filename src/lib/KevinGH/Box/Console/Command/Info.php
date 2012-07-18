<?php

    /* This file is part of Bo.
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
     * The command that displays PHAR information.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Info extends Command
    {
        /** {@inheritDoc} */
        public function configure()
        {
            $this->setName('info')
                 ->setDescription('Display information about the PHAR extension or file.');

            $this->addArgument(
                'phar',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'The PHAR file name.'
            );
        }

        /** {@inheritDoc} */
        public function execute(InputInterface $input, OutputInterface $output)
        {
            if ($phars = $input->getArgument('phar'))
            {
                foreach ($phars as $phar)
                {
                    if (false === file_exists($phar))
                    {
                        $output->writeln("<error>$phar: does not exist</error>\n");

                        continue;
                    }

                    else
                    {
                        $output->writeln("$phar:");
                    }

                    try
                    {
                        $object = new Phar($phar);
                    }

                    catch (UnexpectedValueException $exception)
                    {
                        $output->writeln("<error>$phar: is corrupt</error>\n");

                        continue;
                    }

                    $output->writeln("    - API v" . $object->getVersion());

                    $output->write('    - Compression: ');

                    switch ($compression = $object->isCompressed())
                    {
                        case Phar::BZ2: $output->writeln('BZ2'); break;
                        case Phar::GZ: $output->writeln('GZ'); break;
                        case Phar::TAR: $output->writeln('TAR'); break;
                        case Phar::ZIP: $output->writeln('ZIP'); break;

                        default:
                        {
                            if (false === $compression)
                            {
                                $output->writeln('none');
                            }

                            else
                            {
                                $output->writeln('unrecognized');
                            }
                        }
                    }

                    $output->writeln("    - Metadata: " . ($object->hasMetadata() ? 'Yes' : 'No'));

                    $signature = $object->getSignature();

                    $output->writeln("    - Signature: {$signature['hash_type']}");

                    unset($object);

                    $output->writeln('');
                }
            }

            else
            {
                $output->writeln('PHAR v' . Phar::apiVersion() . "\n");

                $output->writeln('Compression algorithms:');

                foreach (Phar::getSupportedCompression() as $algorithm)
                {
                    $output->writeln("    - $algorithm");
                }

                $output->writeln("\nSignature algorithms:");

                foreach (Phar::getSupportedSignatures() as $algorithm)
                {
                    $output->writeln("    - $algorithm");
                }
            }
        }
    }