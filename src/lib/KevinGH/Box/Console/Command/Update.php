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

use KevinGH\Amend\Command;
use KevinGH\Box\Box;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;

/** {@inheritDoc} */
class Update extends Command
{
    /** {@inheritDoc} */
    protected $extract = '@update_matcher@';

    /** {@inheritDoc} */
    protected $match = '@update_matcher@';

    /** {@inheritDoc} */
    protected $url = '@update_url@';

    /** {@inheritDoc} */
    public function __construct()
    {
        parent::__construct('update');
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->integrity = function ($file) use ($output) {
            try {
                $phar = new Box($file);
            } catch (UnexpectedValueException $e) {
                $output->writeln("<error>The update was corrupted.</error>\n");

                throw $e;
            }
            // @codeCoverageIgnoreStart
        };
        // @codeCoverageIgnoreEnd

        return parent::execute($input, $output);
    }
}

