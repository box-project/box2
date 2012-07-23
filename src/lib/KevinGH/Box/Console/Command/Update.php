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
        KevinGH\Version\Version,
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
        /**
         * The flag that allows updates to the next major version.
         *
         * @type boolean
         */
        private $allowMajor = false;

        /**
         * The current version.
         *
         * @type Version
         */
        private $currentVersion;

        /**
         * The next major update.
         *
         * @type Version
         */
        private $nextMajor;

        /**
         * The update information.
         *
         * @type array
         */
        private $updateInfo;

        /**
         * The update matching regex.
         *
         * @type string
         */
        private $updateMatcher = '@update_matcher@';

        /**
         * The update URL.
         *
         * @type string
         */
        private $updateURL = '@update_url@';

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

            $this->addOption(
                'major',
                'm',
                InputOption::VALUE_NONE,
                'Allow update to next major version.'
            );
        }

        /** {@inheritDoc} */
        public function execute(InputInterface $input, OutputInterface $output)
        {
            $this->allowMajor = $input->getOption('major');

            if ($this->isCurrent() && (false === $input->getOption('force')))
            {
                if ($this->nextMajor)
                {
                    $output->writeln(sprintf(
                        '<info>Box is up-to-date</info><comment> but a major update (%s) is available!</comment>',
                        $this->nextMajor
                    ));
                }

                else
                {
                    $output->writeln('<info>Box is up-to-date.</info>');
                }

                return 0;
            }

            $this->replaceSelf($this->getUpdate());

            if ($this->nextMajor)
            {
                $output->writeln(sprintf(
                    '<info>Box has been updated</info><comment> but a major update (%s) is available!</comment>',
                    $this->nextMajor
                ));
            }

            else
            {
                $output->writeln('<info>Box has been updated!</info>');
            }
        }

        /**
         * Returns the current version.
         *
         * @return Version The version.
         */
        private function getCurrentVersion()
        {
            if (null === $this->currentVersion)
            {
                $this->currentVersion = new Version($this->getApplication()->getVersion());
            }

            return $this->currentVersion;
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
            if (null === $this->updateInfo)
            {
                if (false === ($data = @ file_get_contents($this->updateURL)))
                {
                    $error = error_get_last();

                    throw new RuntimeException(sprintf(
                        'The update information could not be retrieved: %s',
                        $error['message']
                    ));
                }

                $current = $this->getCurrentVersion();

                $downloads = $this->getHelper('json')->parse($this->updateURL, $data);

                $list = array();

                foreach ($downloads as $download)
                {
                    if (preg_match($this->updateMatcher, $download['name']))
                    {
                        $version = new Version(preg_replace($this->updateMatcher, '\\1', $download['name']));

                        if ($this->allowMajor || ($version->getMajor() == $current->getMajor()))
                        {
                            $list[] = array($version, $download);
                        }

                        elseif ($version->getMajor() > $current->getMajor())
                        {
                            if ($this->nextMajor)
                            {
                                if ($version->isGreaterThan($this->nextMajor))
                                {
                                    $this->nextMajor = $version;
                                }
                            }

                            else
                            {
                                $this->nextMajor = $version;
                            }
                        }
                    }
                }

                if (empty($list))
                {
                    throw new RuntimeException('Unable to find any updates.');
                }

                usort($list, function ($a, $b)
                {
                    return $a[0]->compareTo($b[0]);
                });

                $item = array_shift($list);

                $this->updateInfo = array(
                    'name' => $item[1]['name'],
                    'stamp' => new DateTime($item[1]['created_at']),
                    'url' => $item[1]['html_url'],
                    'version' => $item[0]
                );
            }

            return $this->updateInfo;
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
            $version = $this->getApplication()->getVersion();

            if ('git_version' === trim($version, '@'))
            {
                throw new RuntimeException('Use `git pull` to update.');
            }

            $info = $this->getInfo();

            $current = $this->getCurrentVersion();

            return ($current->isEqualTo($info['version']) || $current->isGreaterThan($info['version']));
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

            if (false === @ chmod($_SERVER['argv'][0], 0755))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The application could not be marked as executable: %s',
                    $error['message']
                ));
            }
        }
    }