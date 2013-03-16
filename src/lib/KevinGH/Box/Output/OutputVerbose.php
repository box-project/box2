<?php

namespace KevinGH\Box\Output;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Allows output control by current verbosity setting.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class OutputVerbose extends ConsoleOutput
{
    /**
     * Writes the message only when verbosity is set to VERBOSITY_VERBOSE.
     *
     * @see OutputInterface#write
     */
    public function verbose($message, $newline = false, $type = 0)
    {
        if (OutputInterface::VERBOSITY_VERBOSE === $this->getVerbosity()) {
            $this->write($message, $newline, $type);
        }
    }

    /**
     * Writes the message only when verbosity is set to VERBOSITY_VERBOSE
     *
     * @see OutputInterface#writeln
     */
    public function verboseln($message, $type = 0)
    {
        $this->verbose($message, true, $type);
    }
}