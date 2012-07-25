<?php

    /**
     * The version in name regex.
     *
     * @type string
     */
    define('REGEX', '/^box\-(.+?)\.phar$/');

    /**
     * The download API URL.
     *
     * @type string
     */
    define('URL', 'https://api.github.com/repos/kherge/Box/downloads');

    makeErrorsIntoExceptions();

    echo "Checking requirements...\n";

    checkRequirements();

    echo "Downloading Box...\n";

    installBox(getLatestURL());

    echo "Box has been installed!\n";

    /**
     * Checks the PHP installation for missing support.
     */
    function checkRequirements()
    {
        if (false === version_compare(PHP_VERSION, '5.3.3', '>='))
        {
            error('PHP v5.3.3 or greater is required.');
        }

        if (false === extension_loaded('phar'))
        {
            error('The "phar" extension is required.');
        }

        $extension = new ReflectionExtension('phar');

        if (false === version_compare($extension->getVersion(), '2.0', '>='))
        {
            error('The PHP "phar" extension v2.0 or greater is required.');
        }

        if (false === extension_loaded('openssl'))
        {
            echo "\nWarning: The \"openssl\" extension is not available.\n";
            echo "         You will not be able to create or verify PHARs\n";
            echo "         using OpenSSL signatures.\n\n";
        }

        if (true == ini_get('phar.readonly'))
        {
            echo "\nWarning: The \"phar.readonly\" INI setting is set to \"On\".\n";
            echo "         You will not be able to create or modifying existing\n";
            echo "         PHARs.\n\n";
        }
    }

    /**
     * Exits with an error message.
     *
     * @param string $message The message.
     * @param mixed $arg,... A value.
     */
    function error($message)
    {
        if (func_num_args() > 1)
        {
            $message = vsprintf($message, array_slice(func_get_args(), 1));
        }

        fputs(STDERR, "$message\n");

        exit(1);
    }

    /**
     * Finds the latest download URL and returns it.
     *
     * @return string The latest download URL.
     */
    function getLatestURL()
    {
        $downloads = json_decode(file_get_contents(URL), true);

        $latest = null;

        $url = null;

        foreach ($downloads as $download)
        {
            if (preg_match(REGEX, $download['name']))
            {
                $version = new Version(preg_replace(REGEX, '\\1', $download['name']));

                if ((null === $latest) || $version->isGreaterThan($latest))
                {
                    $latest = $version;

                    $url = $download['html_url'];
                }
            }
        }

        if (null === $url)
        {
            error('No downloads were found.');
        }

        return $url;
    }

    /**
     * Downloads, verifies, and installs the Box application.
     */
    function installBox($url)
    {
        unlink($temp = tempnam(sys_get_temp_dir(), 'box'));

        mkdir($temp);

        $temp = "$temp/box.phar";
        $in = fopen($url, 'rb');
        $out = fopen($temp, 'wb');

        while (false === feof($in))
        {
            fwrite($out, fread($in, 4096));
        }

        fclose($out);
        fclose($in);

        try
        {
            $phar = new Phar($temp);
        }

        catch (PharException $exception)
        {
            error('The download was corrupted: %s', $exception->getMessage());
        }

        catch (UnexpectedValueException $exception)
        {
            error('The download was corrupted: %s', $exception->getMessage());
        }

        rename($temp, 'box.phar');
    }

    /**
     * Converts errors into exceptions.
     */
    function makeErrorsIntoExceptions()
    {
        set_error_handler(function($code, $message, $file, $line)
        {
            throw new ErrorException($message, 0, $code, $file, $line);
        });
    }

    /**
     * Manages a semantic version string.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Version
    {
        /**
         * The semantic version regular expression.
         *
         * @type string
         */
        const REGEX = '/^v{0,1}([0-9]+\.{0,1}){1,3}(\-([a-z0-9]+\.{0,1})+){0,1}(\+(build\.{0,1}){0,1}([a-z0-9]+\.{0,1}){0,}){0,1}$/';

        /**
         * The build information.
         *
         * @type array
         */
        private $build;

        /**
         * The major version number.
         *
         * @type integer
         */
        private $major = 0;

        /**
         * The minor version number.
         *
         * @type integer
         */
        private $minor = 0;

        /**
         * The patch number.
         *
         * @type integer
         */
        private $patch = 0;

        /**
         * The pre-release information.
         *
         * @type array
         */
        private $pre;

        /**
         * Parses the string representation of the version information.
         *
         * @param array $pre The recognized pre-releases.
         */
        public function __construct($string = '')
        {
            if (false === empty($string))
            {
                $this->parseString($string);
            }
        }

        /**
         * Generates a string using the current version information.
         *
         * @return string The string representation of the information.
         */
        public function __toString()
        {
            $string = sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);

            if ($this->pre)
            {
                $string .= '-' . join('.', $this->pre);
            }

            if ($this->build)
            {
                $string .= '+' . join('.', $this->build);
            }

            return $string;
        }

        /**
         * Compares one version to another.
         *
         * @param Version $version Another version.
         * @return -1 If this one is greater, 0 if equal, or 1 if $version is greater.
         */
        public function compareTo($version)
        {
            $major = $version->getMajor();
            $minor = $version->getMinor();
            $patch = $version->getPatch();
            $pre = $version->getPreRelease();
            $build = $version->getBuild();

            if ($this->major > $major) return -1;
            if ($this->major < $major) return 1;
            if ($this->minor > $minor) return -1;
            if ($this->minor < $minor) return 1;
            if ($this->patch > $patch) return -1;
            if ($this->patch < $patch) return 1;

            if (empty($this->pre) && $pre)
            {
                return -1;
            }

            if ($this->pre && empty($pre))
            {
                return 1;
            }

            if ($pre || $this->pre)
            {
                if (0 !== ($weight = $this->precedence($this->pre, $pre)))
                {
                    return $weight;
                }
            }

            if ($build || $this->build)
            {
                if ((null === $this->build) && $build)
                {
                    return 1;
                }

                if ($this->build && (null === $build))
                {
                    return -1;
                }

                return $this->precedence($this->build, $build);
            }

            return 0;
        }

        /**
         * Checks if the version is equal to the given one.
         *
         * @param Version $version The version to compare against.
         * @return boolean TRUE if equal, FALSE if not.
         */
        public function isEqualTo(Version $version)
        {
            return ((string) $this == (string) $version);
        }

        /**
         * Checks if this version is greater than the given one.
         *
         * @param Version $version The version to compare against.
         * @return boolean TRUE if greater, FALSE if not.
         */
        public function isGreaterThan(Version $version)
        {
            return (0 > $this->compareTo($version));
        }

        /**
         * Checks if this version is less than the given one.
         *
         * @param Version $version The version to compare against.
         * @return boolean TRUE if less than, FALSE if not.
         */
        public function isLessThan(Version $version)
        {
            return $version->isGreaterThan($this);
        }

        /**
         * Checks if the string is a valid string representation of a version.
         *
         * @param string $string The string.
         * @return boolean TRUE if valid, FALSE if not.
         */
        public static function isValid($string)
        {
            return (bool) preg_match(static::REGEX, $string);
        }

        /**
         * Returns the build version information.
         *
         * @return array|null The build version information.
         */
        public function getBuild()
        {
            return $this->build;
        }

        /**
         * Returns the pre-release version information.
         *
         * @return array|null The pre-release version information.
         */
        public function getPreRelease()
        {
            return $this->pre;
        }

        /**
         * Returns the major version number.
         *
         * @return integer The major version number.
         */
        public function getMajor()
        {
            return $this->major;
        }

        /**
         * Returns the minor version number.
         *
         * @return integer The minor version number.
         */
        public function getMinor()
        {
            return $this->minor;
        }

        /**
         * Returns the patch version number.
         *
         * @return integer The patch version number.
         */
        public function getPatch()
        {
            return $this->patch;
        }

        /**
         * Parses the version string, replacing current any data.
         *
         * @throws InvalidArgumentException If the string is invalid.
         * @param string $string The string representation.
         */
        public function parseString($string)
        {
            $this->build = null;
            $this->major = 0;
            $this->minor = 0;
            $this->patch = 0;
            $this->pre = null;

            if (false === static::isValid($string))
            {
                throw new InvalidArgumentException(sprintf(
                    'The version string "%s" is invalid.',
                    $string
                ));
            }

            if (false !== strpos($string, '+'))
            {
                list($string, $build) = explode('+', $string);

                $this->setBuild(explode('.', $build));
            }

            if (false !== strpos($string, '-'))
            {
                list($string, $pre) = explode('-', $string);

                $this->setPreRelease(explode('.', $pre));
            }

            $version = explode('.', $string);

            $this->major = (int) $version[0];

            if (isset($version[1]))
            {
                $this->minor = (int) $version[1];
            }

            if (isset($version[2]))
            {
                $this->patch = (int) $version[2];
            }
        }

        /**
         * Sets the build version information.
         *
         * @param array|integer|string $build The build version information.
         */
        public function setBuild($build)
        {
            $this->build = array_values((array) $build);

            array_walk($this->build, function(&$v)
            {
                if (preg_match('/^[0-9]+$/', $v))
                {
                    $v = (int) $v;
                }
            });
        }

        /**
         * Sets the pre-release version information.
         *
         * @param array|integer|string $pre The pre-release version information.
         */
        public function setPreRelease($pre)
        {
            $this->pre = array_values((array) $pre);

            array_walk($this->pre, function(&$v)
            {
                if (preg_match('/^[0-9]+$/', $v))
                {
                    $v = (int) $v;
                }
            });
        }

        /**
         * Sets the major version number.
         *
         * @param integer|string $major The major version number.
         */
        public function setMajor($major)
        {
            $this->major = (int) $major;
        }

        /**
         * Sets the minor version number.
         *
         * @param integer|string $minor The minor version number.
         */
        public function setMinor($minor)
        {
            $this->minor = (int) $minor;
        }

        /**
         * Sets the patch version number.
         *
         * @param integer|string $patch The patch version number.
         */
        public function setPatch($patch)
        {
            $this->patch = (int) $patch;
        }

        /**
         * Checks the precedence of each data set.
         *
         * @param array $a A data set.
         * @param array $b A data set.
         * @return integer -1 if $a > $b, 0 if $a = $b, 1 if $a < $b.
         */
        private function precedence($a, $b)
        {
            if (count($a) > count($b))
            {
                $l = -1;
                $r = 1;
                $x = $a;
                $y = $b;
            }

            else
            {
                $l = 1;
                $r = -1;

                $x = $b;
                $y = $a;
            }

            foreach (array_keys($x) as $i)
            {
                if (false === isset($y[$i]))
                {
                    return $l;
                }

                if ($x[$i] === $y[$i])
                {
                    continue;
                }

                $xi = is_integer($x[$i]);
                $yi = is_integer($y[$i]);

                if ($xi && $yi)
                {
                    return ($x[$i] > $y[$i]) ? $l : $r;
                }

                elseif ((false === $xi) && (false === $yi))
                {
                    return (max($x[$i], $y[$i]) == $x[$i]) ? $l : $r;
                }

                else
                {
                    return $xi ? $r : $l;
                }
            }

            return 0;
        }
    }
