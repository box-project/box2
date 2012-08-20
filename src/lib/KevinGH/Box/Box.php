<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box;

use Closure;
use InvalidArgumentException;
use Phar;
use RuntimeException;

/**
 * Simplifies the process of creating new PHARs.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class Box extends Phar
{
    /**
     * The alias.
     *
     * @type string
     */
    private $alias;

    /**
     * The user-defined compact callback.
     *
     * @type Closure
     */
    private $compactor;

    /**
     * Include interceptFileFuncs() in stub?
     *
     * @type boolean
     */
    private $intercept = false;

    /**
     * The relative path of the main script.
     *
     * @type string
     */
    private $main;

    /**
     * The file name of the PHAR.
     *
     * @type string
     */
    private $name;

    /**
     * The replacement values.
     *
     * @type array
     */
    private $replacements = array();

    /** {@inheritDoc} */
    public function __construct($fname, $flags = 0, $alias = null)
    {
        $this->alias = $alias;
        $this->name = $fname;

        parent::__construct($fname, $flags, $alias);
    }

    /**
     * Compacts the source code, making it smaller while preserving line count.
     *
     * @param string $source The source code.
     *
     * @return string The compacted source code.
     */
    public function compactSource($source)
    {
        $temp = '';

        foreach (token_get_all($source) as $token) {
            if (is_string($token)) {
                $temp .= $token;
            } elseif ((T_COMMENT === $token[0]) || (T_DOC_COMMENT === $token[0])) {
                $temp .= str_repeat("\n", substr_count($token[1], "\n"));
            } elseif (T_WHITESPACE === $token[0]) {
                $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                $whitespace = preg_replace('{\n +}', "\n", $whitespace);

                $temp .= $whitespace;
            } else {
                $temp .= $token[1];
            }
        }

        $source = $temp;

        unset($temp);

        if ($this->compactor) {
            $source = call_user_func($this->compactor, $source);
        }

        return $source;
    }

    /**
     * Creates the a stub using the alias, and main script if available.
     *
     * @return string The default stub.
     */
    public function createStub()
    {
        $stub = <<<STUB
#!/usr/bin/env php
<?php

/**
 * Genereated by Box: http://github.com/kherge/Box
 */

Phar::mapPhar('{$this->alias}');


STUB
        ;

        if ($this->intercept) {
            $stub .= <<<STUB
Phar::interceptFileFuncs();


STUB
            ;
        }

        if ($this->main) {
            $stub .= <<<STUB
require 'phar://{$this->alias}/{$this->main}';


STUB
            ;
        }

        $stub .= "__HALT_COMPILER();";

        return $stub;
    }

    /**
     * Replaces placeholders in the source code with their real values.
     *
     * @param string $source The source code.
     *
     * @return string The replaced source code.
     */
    public function doReplacements($source)
    {
        foreach ($this->replacements as $key => $value) {
            $source = str_replace("@$key@", $value, $source);
        }

        return $source;
    }

    /**
     * Imports a file's source code after compacting and doing replacements.
     *
     * @param string  $relative The relative file path.
     * @param string  $absolute The absolute file path.
     * @param boolean $main     Is it the main program code?
     *
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException If the file could not be imported.
     */
    public function importFile($relative, $absolute, $main = false)
    {
        if (false === is_file($absolute)) {
            throw new InvalidArgumentException(sprintf(
                'The path "%s" is not a file or it does not exist.',
                $absolute
            ));
        }

        if (false === ($source = @ file_get_contents($absolute))) {
            $error = error_get_last();

            throw new RuntimeException(sprintf(
                'The file "%s" could not be read: %s',
                $absolute,
                $error['message']
            ));
        }

        $this->importSource($relative, $source, $main);
    }

    /**
     * Imports the source code after compacting and doing replacements.
     *
     * @param string  $relative The relative file path.
     * @param string  $source   The source code.
     * @param boolean $main     Is it the main program code?
     */
    public function importSource($relative, $source, $main = false)
    {
        if (false !== strpos($source, '<?php')) {
            $source = $this->compactSource($source);
        }

        $source = $this->doReplacements($source);

        if ($main) {
            $this->main = $relative;

            $source = preg_replace('/^#!.*\s*/', '', $source);
        }

        $this->addFromString($relative, $source);
    }

    /**
     * Sets the custom compactor.
     *
     * @param Closure $compactor The compactor closure.
     */
    public function setCompactor(Closure $compactor)
    {
        $this->compactor = $compactor;
    }

    /**
     * Toggles the interceptFileFunc() flag for the generated stub.
     *
     * @param boolean $toggle The new intercept state.
     */
    public function setIntercept($toggle)
    {
        $this->intercept = (bool) $toggle;
    }

    /**
     * Sets the replacement values.
     *
     * @param array $replacements The replacement values.
     */
    public function setReplacements(array $replacements)
    {
        $this->replacements = $replacements;
    }

    /**
     * Sets the stub using a file.
     *
     * @param string  $file    The file path.
     * @param boolean $replace Do replacements?
     *
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException         If the file could not be read.
     */
    public function setStubFile($file, $replace = false)
    {
        if (false === is_file($file)) {
            throw new InvalidArgumentException(sprintf(
                'The path "%s" is not a file or it does not exist.',
                $file
            ));
        }

        if (false === ($contents = file_get_contents($file))) {
            $error = error_get_last();

            throw new RuntimeException(sprintf(
                'The stub file "%s" could not be read: %s',
                $file,
                $error['message']
            ));
        }

        if ($replace) {
            $contents = $this->doReplacements($contents);
        }

        $this->setStub($contents);
    }

    /**
     * Signs the PHAR using a private key file.
     *
     * @param string $file The private key file.
     * @param string $pass The passhphrase.
     *
     * @throws InvalidArgumentException If the private key does not exist.
     * @throws RuntimeException If the public key could not be retrieved.
     */
    public function usePrivateKeyFile($file, $pass = null)
    {
        if (false === is_file($file)) {
            throw new InvalidArgumentException(sprintf(
                'The path "%s" is not a file or it does not exist.',
                $file
            ));
        }

        if (false === extension_loaded('openssl')) {
            throw new RuntimeException(sprintf(
                'The "openssl" extension is not available.'
            ));
        }

        if (false === ($pem = @ file_get_contents($file))) {
            $error = error_get_last();

            throw new RuntimeException(sprintf(
                'The private key file "%s" could not be read: %s',
                $file,
                $error['message']
            ));
        }

        if (false === ($resource = openssl_pkey_get_private($pem, $pass))) {
            throw new RuntimeException(sprintf(
                'The private key file "%s" could not be parsed: %s',
                $file,
                openssl_error_string()
            ));
        }

        if (false === openssl_pkey_export($resource, $private)) {
            throw new RuntimeException(sprintf(
                'The private key file "%s" could not be exported: %s',
                $file,
                openssl_error_string()
            ));
        }

        if (false === ($details = openssl_pkey_get_details($resource))) {
            throw new RuntimeException(sprintf(
                'The details of the private key file "%s" could not be retrieved: %s',
                $file,
                openssl_error_string()
            ));
        }

        $public = $details['key'];

        $this->setSignatureAlgorithm(self::OPENSSL, $private);

        if (false === @ file_put_contents($this->name . '.pubkey', $public)) {
            $error = error_get_last();

            throw new RuntimeException(sprintf(
                'The public key file "%s" could not be written: %s',
                $this->name . '.pubkey',
                $error['message']
            ));
        }
    }
}

