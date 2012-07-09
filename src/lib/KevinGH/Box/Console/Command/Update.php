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

    use DateTime,
        KevinGH\Box\Console\Application,
        KevinGH\Box\Box,
        KevinGH\Box\Console\Exception\JSONException,
        PharException,
        RuntimeException,
        Symfony\Component\Console\Command\Command,
        Symfony\Component\Console\Input\InputInterface,
        Symfony\Component\Console\Input\InputOption,
        Symfony\Component\Console\Output\OutputInterface,
        UnexpectedValueException;

    /**
     * Allows the Box application to self-update.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Update extends Command
    {
        /** {@inheritDoc} */
        public function configure()
        {
            $this->setName('update')
                 ->setDescription('Updates the Box application.');

            $this->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Re-download if already current version.'
            );
        }

        /** {@inheritDoc} */
        public function execute(InputInterface $input, OutputInterface $output)
        {
            if ($this->isCurrent() && (false === $input->getOption('force')))
            {
                $output->writeln('<info>Box is up-to-date.</info>');

                return 0;
            }

            $this->replaceSelf($this->getUpdate());

            $output->writeln('<info>Box has been updated!</info>');
        }

        /**
         * Retrieves the update information from GitHub.
         *
         * @throws JSONException If the update info is invalid.
         * @throws RuntimeException If the update info could not be download.
         * @return array The update information.
         */
        private function getInfo()
        {
            static $info;

            if (null === $info)
            {
                if (false === ($data = @ file_get_contents('@update_url@')))
                {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'The update information could not be retrieved: %s',
                        $error['message']
                    ));
                }

                if (null === ($data = json_decode($data, true)))
                {
                    throw new JSONException(json_last_error());
                }

                foreach ($data as $item)
                {
                    if ('@update_name@' == $item['name'])
                    {
                        break;
                    }

                    unset($item);
                }

                if (false === isset($item))
                {
                    throw new RuntimeException('Unable to find any updates.');
                }

                $info = array(
                    'name' => $item['name'],
                    'stamp' => new DateTime($item['created_at']),
                    'url' => $item['html_url'],
                    'version' => $item['description']
                );

                if (preg_match('/^[a-f0-9]{40}$/', $info['version']))
                {
                    $info['version'] = substr($info['version'], 0, 7);
                }
            }

            return $info;
        }

        /**
         * Downloads and verifies the update in a temporary location.
         *
         * @throws RuntimeException If the update could not be downloaded.
         * @return string The temporary file.
         */
        private function getUpdate()
        {
            unlink($temp = tempnam(sys_get_temp_dir(), 'box'));

            mkdir($temp);

            $info = $this->getInfo();

            $temp .= DIRECTORY_SEPARATOR . $info['name'];

            if (false === ($in = @ fopen($info['url'], 'rb')))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The update file could not be opened: %s',
                    $error['message']
                ));
            }

            if (false === ($out = @ fopen($temp, 'wb')))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The temporary file could not be opened: %s',
                    $error['message']
                ));
            }

            while (false === feof($in))
            {
                if (false === ($buffer = @ fread($in, 4096)))
                {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'The update file could not be read: %s',
                        $error['message']
                    ));
                }

                if (false === @ fwrite($out, $buffer))
                {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'The temporary file could not be written: %s',
                        $error['message']
                    ));
                }
            }

            fclose($out);
            fclose($in);

            try
            {
                $box = new Box($temp, 0, null);
            }

            catch (PharException $exception)
            {
                throw new RuntimeException(sprintf(
                    'The PHAR is corrupt: %s',
                    $exception->getMessage()
                ));
            }

            catch (UnexpectedValueException $exception)
            {
                throw new RuntimeException(sprintf(
                    'The PHAR is corrupt: %s',
                    $exception->getMessage()
                ));
            }

            unset($box);

            return $temp;
        }

        /**
         * Checks if we're running the current verison.
         *
         * @throws RuntimeException If using a git repo.
         * @return boolean TRUE if current, FALSE if not.
         */
        private function isCurrent()
        {
            if ('git_version' === trim(Application::VERSION, '@'))
            {
                throw new RuntimeException('Use `git pull` to update.');
            }

            $info = $this->getInfo();

            return (Application::VERSION == $info['version']);
        }

        /**
         * Replaces the application with the update.
         *
         * @throws RuntimeException If the app could not be replaced.
         * @param string $update The update file.
         */
        private function replaceSelf($update)
        {
            if (false === @ rename($update, $_SERVER['argv'][0]))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The application could not be replaced: %s',
                    $error['message']
                ));
            }
        }
    }