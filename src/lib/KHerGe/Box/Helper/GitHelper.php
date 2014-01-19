<?php

namespace KHerGe\Box\Helper;

use RuntimeException;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Process\Process;

/**
 * Manages discovering the Git tag or commit hash.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class GitHelper extends Helper
{
    /**
     * Returns the most recent commit hash.
     *
     * @param string  $dir   The working directory path.
     * @param boolean $short Return the short commit hash?
     *
     * @return string If `$short` is true, the short commit hash  is returned.
     *                Otherwise, the long commit hash is returned.
     */
    public function getCommit($dir, $short = false)
    {
        return $this->runCommand(
            sprintf(
                'git log --pretty="%s" -n1 HEAD',
                $short ? '%h' : '%H'
            ),
            $dir
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'git';
    }

    /**
     * Returns the most recent Git tag.
     *
     * @param string $dir The working directory path.
     *
     * @return string If a tag is available, it is returned. Otherwise,
     *                nothing (`null) is returned.
     */
    public function getTag($dir)
    {
        return $this->runCommand('git describe --tags HEAD', $dir);
    }

    /**
     * Returns the result for the command string.
     *
     * @param string $command The command string.
     * @param string $dir     The working directory path.
     *
     * @return string The output from the command.
     *
     * @throws RuntimeException If there is a problem running the command.
     */
    private function runCommand($command, $dir)
    {
        $process = new Process($command, $dir);

        if (0 === $process->run()) {
            return trim($process->getOutput()) ?: null;
        }

        throw new RuntimeException(
            sprintf(
                "Git repository error:\n\nOutput:\n%s\n\nError:%s",
                $process->getOutput() ?: '(none)',
                $process->getErrorOutput() ?: '(none)'
            )
        );
    }
}
