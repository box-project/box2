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

use ErrorException;
use KevinGH\Amend;
use KevinGH\Box\Console\Helper;
use KevinGH\Elf;
use Symfony\Component\Console\Application as _Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var string
     *
     * @api
     */
    const VERSION = '@package_version@';

    /** {@inheritDoc} */
    public function __construct($name = 'Box', $version = self::VERSION)
    {
        parent::__construct($name, $version);

        set_error_handler(
            function (
                $code,
                $message,
                $file,
                $line
            ) {
                if (error_reporting() & $code) {
                    throw new ErrorException($message, 0, $code, $file, $line);
                }
            }
        );
    }

    /**
     * Creates the console output stream.
     *
     * @return ConsoleOutput The output stream.
     */
    public function createOutput()
    {
        $output = new ConsoleOutput();
        $formatter = $output->getFormatter();

        $formatter->setStyle('error', new OutputFormatterStyle('red'));
        $formatter->setStyle('prefix', new OutputFormatterStyle('cyan'));
        $formatter->setStyle('question', new OutputFormatterStyle('magenta'));

        return $output;
    }

    /** {@inheritDoc} */
    public function getDefaultCommands()
    {
        $commands = parent::getDefaultCommands();

        $commands[] = new Command\Add();
        $commands[] = new Command\Build();
        $commands[] = new Command\Extract();
        $commands[] = new Command\Info();
        $commands[] = new Command\Remove();
        $commands[] = new Command\Validate();
        $commands[] = new Command\Verify();

        if (extension_loaded('openssl')) {
            $commands[] = new Command\OpenSsl\CreatePrivate();
            $commands[] = new Command\OpenSsl\ExtractPublic();
        }

        if (false === strpos($this->getVersion(), 'package_version')) {
            $commands[] = new Command\Update('update');
        }

        return $commands;
    }

    /** {@inheritDoc} */
    public function getDefaultHelperSet()
    {
        $helperSet = parent::getDefaultHelperSet();

        $helperSet->set(new Elf\Git());
        $helperSet->set(new Elf\Json());
        $helperSet->set(new Helper\Box());

        if (extension_loaded('openssl')) {
            $helperSet->set(new Elf\OpenSsl());
        }

        if (false === strpos($this->getVersion(), 'package_version')) {
            $helperSet->set(new Amend\Helper('@manifest_url@'));
            $this->add(new Amend\Command('update'));
        }

        return $helperSet;
    }

    /** {@inheritDoc} */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        // @codeCoverageIgnoreStart
        if (null === $output) {
            $output = $this->createOutput();
        }

        return parent::run($input, $output);
        // @codeCoverageIgnoreEnd
    }
}

