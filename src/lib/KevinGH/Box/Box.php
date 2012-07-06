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

    use Closure,
        InvalidArgumentException,
        Phar,
        RuntimeException,
        Symfony\Component\Finder\Finder;

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
        public function __construct($fname, $flags = 0, $alias = 'default.phar')
        {
            $this->alias = $alias;

            $this->name = $fname;

            parent::__construct($fname, $flags, $alias);
        }

        /**
         * Compacts the source code, making it smaller while preserving line count.
         *
         * @param string $source The source code.
         * @return string The compacted source code.
         */
        public function compactSource($source)
        {
            $temp = '';

            foreach (token_get_all($source) as $token)
            {
                if (is_string($token))
                {
                    $temp .= $token;
                }

                elseif ((T_COMMENT === $token[0]) || (T_DOC_COMMENT === $token[0]))
                {
                    $temp .= str_repeat("\n", substr_count($token[1], "\n"));
                }

                elseif (T_WHITESPACE === $token[0])
                {
                    $whitespace = preg_replace('{[ \t]+}', ' ', $token[1]);
                    $whitespace = preg_replace('{(?:\r\n|\r|\n)}', "\n", $whitespace);
                    $whitespace = preg_replace('{\n +}', "\n", $whitespace);

                    $temp .= $whitespace;
                }

                else
                {
                    $temp .= $token[1];
                }
            }

            $source = $temp;

            unset($temp);

            if ($this->compactor)
            {
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

            if ($this->main)
            {
                $stub .= <<<STUB
    require 'phar://{$this->alias}/{$this->main}';


STUB
                ;
            }

            $stub .= "    __HALT_COMPILER();";

            return $stub;
        }

        /**
         * Replaces placeholders in the source code with their real values.
         *
         * @param string $source The source code.
         * @return string The replaced source code.
         */
        public function doReplacements($source)
        {
            foreach ($this->replacements as $key => $value)
            {
                $source = str_replace("@$key@", $value, $source);
            }

            return $source;
        }

        /**
         * Imports a file's source code after compacting and doing replacements.
         *
         * @throws InvalidArgumentException If the file does not exist.
         * @throws RuntimeException If the file could not be imported.
         * @param string $relative The relative file path.
         * @param string $absolute The absolute file path.
         * @param boolean $main Is it the main program code?
         */
        public function importFile($relative, $absolute, $main = false)
        {
            if (false === file_exists($absolute))
            {
                throw new InvalidArgumentException(sprintf(
                    'The file does not exist: %s',
                    $absolute
                ));
            }

            if (false === ($source = @ file_get_contents($absolute)))
            {
                $error = error_get_last();

                throw new RuntimeException(sprintf(
                    'The file could not be read: %s',
                    $error['message']
                ));
            }

            $this->importSource($relative, $source, $main);
        }

        /**
         * Imports the source code after compacting and doing replacements.
         *
         * @param string $relative The relative file path.
         * @param string $source The source code.
         * @param boolean $main Is it the main program code?
         */
        public function importSource($relative, $source, $main = false)
        {
            if (false !== strpos($source, '<?php'))
            {
                $source = $this->compactSource($source);
            }

            $source = $this->doReplacements($source);

            if ($main)
            {
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
         * Sets the replacement values.
         *
         * @param array $replacements The replacement values.
         */
        public function setReplacements(array $replacements)
        {
            $this->replacements = $replacements;
        }
    }