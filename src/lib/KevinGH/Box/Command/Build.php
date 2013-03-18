<?php

namespace KevinGH\Box\Command;

use Herrera\Box\Box;
use Herrera\Box\StubGenerator;
use KevinGH\Box\Configuration;
use SplFileInfo;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

/**
 * Builds a new Phar.
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class Build extends Configurable
{
    /**
     * The Box instance.
     *
     * @var Box
     */
    private $box;

    /**
     * The configuration settings.
     *
     * @var Configuration
     */
    private $config;

    /**
     * The output handler.
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * @override
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('build');
        $this->setDescription('Builds a new Phar.');
    }

    /**
     * @override
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->config = $this->getConfig($input);
        $path = $this->config->getOutputPath();

        // remove any previous work
        if (file_exists($path)) {
            $this->putln('?', 'Removing previously built Phar...');

            unlink($path);
        }

        // set up Box
        if ($this->isVerbose()) {
            $this->putln('*', 'Building...');
        } else {
            $output->writeln('Building...');
        }

        $this->putln('?', "Output path: $path");

        $this->box = Box::create($path);

        // set replacement values, if any
        if (array() !== ($values = $this->config->getProcessedReplacements())) {
            $this->putln('?', 'Setting replacement values...');

            if ($this->isVerbose()) {
                foreach ($values as $key => $value) {
                    $this->putln('+', "$key: $value");
                }
            }

            $this->box->setValues($values);

            unset($values, $key, $value);
        }

        // register configured compactors
        if (array() !== ($compactors = $this->config->getCompactors())) {
            $this->putln('?', 'Registering compactors...');

            foreach ($compactors as $compactor) {
                $this->putln('+', get_class($compactor));

                $this->box->addCompactor($compactor);
            }
        }

        // start adding files
        $this->add(
            $this->config->getFilesIterator(),
            'Adding files...'
        );
        $this->add(
            $this->config->getBinaryFilesIterator(),
            'Adding binary files...',
            true
        );
        $this->add(
            $this->config->getDirectoriesIterator(),
            'Adding directories...'
        );
        $this->add(
            $this->config->getBinaryDirectoriesIterator(),
            'Adding binary directories...',
            true
        );

        if (array() !== ($iterators = $this->config->getFinders())) {
            $this->putln('?', 'Adding Finder files...');

            foreach ($iterators as $iterator) {
                $this->add($iterator, null);
            }
        }

        if (array() !== ($iterators = $this->config->getBinaryFinders())) {
            $this->putln('?', 'Adding binary Finder files...');

            foreach ($iterators as $iterator) {
                $this->add($iterator, null, true);
            }
        }

        if (null !== ($main = $this->config->getMainScriptPath())) {
            $this->putln(
                '?',
                'Adding main file: '
                    . $this->config->getBasePath()
                    . DIRECTORY_SEPARATOR
                    . $main
            );

            $this->box->addFromString(
                $main,
                $this->config->getMainScriptContents()
            );
        }

        // set the appropriate stub
        if (true === $this->config->isStubGenerated()) {
            $this->putln('?', 'Generating new stub...');

            $this->box->getPhar()->setStub(
                StubGenerator::create()
                    ->alias($this->config->getAlias())
                    ->index($this->config->getMainScriptPath())
                    ->intercept($this->config->isInterceptFileFuncs())
                    ->mimetypes($this->config->getMimetypeMapping())
                    ->mung($this->config->getMungVariables())
                    ->notFound($this->config->getNotFoundScriptPath())
                    ->web($this->config->isWebPhar())
                    ->generate()
            );
        } elseif (null !== ($path = $this->config->getStubPath())) {
            $path = $this->config->getBasePath() . DIRECTORY_SEPARATOR . $path;

            $this->putln('?', "Using stub file: $path");

            $this->box->setStubUsingFile($path);
        } else {
            $this->putln('?', 'Using default stub.');
        }

        // set metadata, if any
        if (null !== ($metadata = $this->config->getMetadata())) {
            $this->putln('?', 'Setting metadata...');

            $this->box->getPhar()->setMetadata($metadata);
        }

        // compress, if algorithm set
        if (null !== ($algorithm = $this->config->getCompressionAlgorithm())) {
            $this->putln('?', 'Compressing...');

            $this->box->getPhar()->compressFiles($algorithm);
        }

        // sign using private key, if applicable
        if (null !== ($key = $this->config->getPrivateKeyPath())) {
            $this->putln('?', 'Signing using a private key...');

            $passphrase = $this->config->getPrivateKeyPassphrase();

            if ($this->config->isPrivateKeyPrompt()) {
                /** @var $dialog DialogHelper */
                $dialog = $this->getHelper('dialog');
                $passphrase = $dialog->askHiddenResponse(
                    $output,
                    'Private key passphrase:'
                );
            }

            $this->box->signUsingFile($key, $passphrase);
        }

        unset($this->box);

        // chmod, if configured
        if (null !== ($chmod = $this->config->getFileMode())) {
            $this->putln('?', 'Setting file permissions...');

            chmod($path, $chmod);
        }

        $this->putln('*', 'Done.');
    }

    /**
     * Adds files using an iterator.
     *
     * @param Traversable $iterator The iterator.
     * @param string      $message  The message to announce.
     * @param boolean     $binary   Should the adding be binary-safe?
     */
    private function add(
        Traversable $iterator = null,
        $message = null,
        $binary = false
    ){
        if ($iterator) {
            if ($message) {
                $this->putln('?', $message);
            }

            $box = $binary ? $this->box->getPhar() : $this->box;
            $base = $this->config->getBasePath();
            $baseRegex = $this->config->getBasePathRegex();

            if ($this->isVerbose()) {
                foreach ($iterator as $file) {
                    /** @var $file SplFileInfo */

                    $this->putln('+', $file);

                    $box->addFile(
                        $file,
                        preg_replace($baseRegex, '', $file->getPathname())
                    );
                }
            } else {
                $box->buildFromIterator($iterator, $base);
            }
        }
    }

    /**
     * Checks if the output handler is verbose.
     *
     * @return boolean TRUE if verbose, FALSE if not.
     */
    public function isVerbose()
    {
        return (OutputInterface::VERBOSITY_VERBOSE === $this->output->getVerbosity());
    }

    /**
     * Outputs a message with a colored prefix.
     *
     * @param string $prefix  The prefix.
     * @param string $message The message.
     */
    private function putln($prefix, $message)
    {
        switch ($prefix) {
            case '*':
                $prefix = "<info>$prefix</info>";
                break;

            case '?':
                $prefix = "<comment>$prefix</comment>";
                break;

            case '+':
                $prefix = "  <comment>+</comment>";
                break;
        }

        $this->verboseln("$prefix $message");
    }

    /**
     * Writes the message only when verbosity is set to VERBOSITY_VERBOSE.
     *
     * @see OutputInterface#write
     */
    public function verbose($message, $newline = false, $type = 0)
    {
        if ($this->isVerbose()) {
            $this->output->write($message, $newline, $type);
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