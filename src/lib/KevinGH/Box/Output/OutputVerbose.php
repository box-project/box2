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
     * Checks if the output handler is verbose.
     *
     * @return boolean TRUE if verbose, FALSE if not.
     */
    public function isVerbose()
    {
        return (OutputInterface::VERBOSITY_VERBOSE === $this->getVerbosity());
    }

    /**
     * Writes the message only when verbosity is set to VERBOSITY_VERBOSE.
     *
     * @see OutputInterface#write
     */
    public function verbose($message, $newline = false, $type = 0)
    {
        if ($this->isVerbose()) {
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