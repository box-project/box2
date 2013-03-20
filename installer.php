<?php

Installer::run();

class Installer{
    const MANIFEST = 'http://box-project.org/manifest.json';
    const NAME = 'box.phar';
    public static function run(){self::checkRequirements();self::downloadApp(self::findCurrent());echo "Done!\n";}
    private static function assert($result,$message,$fatal = true){if(false === $result){self::warn($message);if($fatal){exit(1);}}}
    private static function checkRequirements(){echo "Checking requirements...\n";self::assert(version_compare(PHP_VERSION, '5.3.3', '>='),'    - PHP v5.3.3 or greater is required.');self::assert(extension_loaded('phar'),'    - The "phar" extension is required.');$extension = new ReflectionExtension('phar');self::assert(version_compare($extension->getVersion(), '2.0', '>='),'    - The "phar" extension v2.0 or greater is required.');self::assert(extension_loaded('openssl'),'    - OpenSSL not available, signed PHARs are not supported.',false);self::assert(false == ini_get('phar.readonly'),'    - The "phar.readonly" setting is on. PHARs are read-only.',false);}
    private static function downloadApp($url){echo "Downloading...\n";unlink($temp = tempnam(sys_get_temp_dir(), 'box'));mkdir($temp);$temp .= DIRECTORY_SEPARATOR . self::NAME;self::assert(file_put_contents($temp, file_get_contents($url)),'The app could not be downloaded.');try{$phar = new Phar($temp);}catch(PharException $exception){self::warn('The download was corrupted: ' . $exception->getMessage());exit(1);}catch(UnexpectedValueException $exception){self::warn('The download was corrupted: ' . $exception->getMessage());exit(1);}unset($phar);self::assert(rename($temp, self::NAME),"Could not move temporary file here: $temp");}
    private static function findCurrent(){self::assert($manifest = file_get_contents(self::MANIFEST),'Unable to download the app manifest.');$manifest = json_decode($manifest, true);self::assert(JSON_ERROR_NONE === json_last_error(),'The manifest is corrupt or invalid.');foreach ($manifest as $candidate){$candidate['version'] = Version::create($candidate['version']);if(isset($latest)){if($candidate['version']->isGreaterThan($latest['version'])){$latest = $candidate;}}else{$latest = $candidate;}}return $latest['url'];}
    private static function warn($message){fwrite(STDERR, $message . "\n");}
}

class Version{
    const REGEX = '/^\d+\.\d+\.\d+(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/';
    private $build;
    private $major = 0;
    private $minor = 0;
    private $patch = 0;
    private $pre;
    public function __construct($string = ''){if(false === empty($string)){$this->parseString($string);}}
    public function __toString(){$string = sprintf('%d.%d.%d',$this->major,$this->minor,$this->patch);if ($this->pre) {$string .= '-' . join('.', $this->pre);}if ($this->build) {$string .= '+' . join('.', $this->build);}return $string;}
    public function compareTo($version){$major = $version->getMajor();$minor = $version->getMinor();$patch = $version->getPatch();$pre = $version->getPreRelease();$build = $version->getBuild();switch (true) {case ($this->major < $major):return 1;case ($this->major > $major):return -1;case ($this->minor > $minor):return -1;case ($this->minor < $minor):return 1;case ($this->patch > $patch):return -1;case ($this->patch < $patch):return 1;}if ($pre || $this->pre) {if (empty($this->pre) && $pre) {return -1;}if ($this->pre && empty($pre)) {return 1;}if (0 !== ($weight = $this->precedence($this->pre, $pre))) {return $weight;}}if ($build || $this->build) {if ((null === $this->build) && $build) {return 1;}if ($this->build && (null === $build)) {return -1;}return $this->precedence($this->build, $build);}return 0;}
    public static function create($string = ''){return new static($string);}
    public function isEqualTo(Version $version){return ((string)$this == (string)$version);}
    public function isGreaterThan(Version $version){return (0 > $this->compareTo($version));}
    public function isLessThan(Version $version){return (0 < $this->compareTo($version));}
    public function isStable(){return empty($this->pre);}
    public static function isValid($string){return (bool) preg_match(static::REGEX, $string);}
    public function getBuild(){return $this->build;}
    public function getPreRelease(){return $this->pre;}
    public function getMajor(){return $this->major;}
    public function getMinor(){return $this->minor;}
    public function getPatch(){return $this->patch;}
    public function setBuild($build){$this->build = array_values((array)$build);array_walk($this->build,function (&$v) {if (preg_match('/^[0-9]+$/', $v)) {$v = (int)$v;}});}
    public function setPreRelease($pre){$this->pre = array_values((array)$pre);array_walk($this->pre,function (&$v) {if (preg_match('/^[0-9]+$/', $v)) {$v = (int)$v;}});}
    public function setMajor($major){$this->major = (int)$major;}
    public function setMinor($minor){$this->minor = (int)$minor;}
    public function setPatch($patch){$this->patch = (int)$patch;}
    protected function parseString($string){$this->build = null;$this->major = 0;$this->minor = 0;$this->patch = 0;$this->pre = null;if (false === static::isValid($string)) {throw new InvalidArgumentException(sprintf('The version string "%s" is invalid.', $string));}if (false !== strpos($string, '+')) {list($string, $build) = explode('+', $string);$this->setBuild(explode('.', $build));}if (false !== strpos($string, '-')) {list($string, $pre) = explode('-', $string);$this->setPreRelease(explode('.', $pre));}$version = explode('.', $string);$this->major = (int)$version[0];if (isset($version[1])) {$this->minor = (int)$version[1];}if (isset($version[2])) {$this->patch = (int)$version[2];}}
    protected function precedence($a, $b){if (count($a) > count($b)) {$l = -1;$r = 1;$x = $a;$y = $b;} else {$l = 1;$r = -1;$x = $b;$y = $a;}foreach (array_keys($x) as $i) {if (false === isset($y[$i])) {return $l;}if ($x[$i] === $y[$i]) {continue;}$xi = is_integer($x[$i]);$yi = is_integer($y[$i]);if ($xi && $yi) {return ($x[$i] > $y[$i]) ? $l : $r;} elseif ((false === $xi) && (false === $yi)) {return (max($x[$i], $y[$i]) == $x[$i]) ? $l : $r;} else {return $xi ? $r : $l;}}return 0;}
}