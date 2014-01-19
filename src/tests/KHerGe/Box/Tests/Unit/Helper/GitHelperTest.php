<?php

namespace KHerGe\Box\Tests\Unit\Helper;

use KHerGe\Box\Helper\GitHelper;
use Phine\Test\Temp;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Performs unit testing on `GitHelper`
 *
 * @see KHerGe\Box\Helper\GitHelper
 *
 * @author Kevin Herrera <kevin@herrera.io>
 */
class GitHelperTest extends TestCase
{
    /**
     * The Git helper instance being tested.
     *
     * @var GitHelper
     */
    private $helper;

    /**
     * The temporary directory path.
     *
     * @var string
     */
    private $dir;

    /**
     * The temporary file manager.
     *
     * @var Temp
     */
    private $temp;

    /**
     * Make sure that an exception is thrown for errors.
     */
    public function testError()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a git repository'
        );

        $dir = $this->temp->createDir();
        $now = realpath('.');

        chdir($dir);

        try {
            $this->helper->getCommit('/does/not/exist');
        } catch (\Exception $exception) {
            chdir($now);

            throw $exception;
        }
    }

    /**
     * Make sure that we can retrieve the current commit hash.
     */
    public function testGetCommit()
    {
        $long = exec("cd {$this->dir}; git log --pretty=\"%H\" -n1 HEAD");
        $short = substr($long, 0, 7);

        $this->assertEquals(
            $long,
            $this->helper->getCommit($this->dir),
            'The long commit hash should be returned.'
        );

        $this->assertEquals(
            $short,
            $this->helper->getCommit($this->dir, true),
            'The short commit hash should be returned.'
        );
    }

    /**
     * Make sure that the expected helper name is returned.
     */
    public function testGetName()
    {
        $this->assertEquals(
            'git',
            $this->helper->getName(),
            'The expected helper name should be returned.'
        );
    }

    /**
     * Make sure that we can retrieve the current tag.
     */
    public function testGetTag()
    {
        $tag = exec("cd {$this->dir} && git describe --tags HEAD");

        $this->assertEquals(
            $tag,
            $this->helper->getTag($this->dir),
            'The current tag should be returned.'
        );
    }

    /**
     * @override
     */
    protected function setUp()
    {
        $finder = new ExecutableFinder();

        if (null === $finder->find('git')) {
            $this->markTestSkipped('The "git" command is not available.');
        }

        $this->helper = new GitHelper();
        $this->temp = new Temp();
        $this->dir = $this->temp->createDir();

        exec("cd {$this->dir} && git init");
        touch($this->dir . '/test');
        exec("cd {$this->dir} && git add test");
        exec("cd {$this->dir} && git commit -m \"Creating a test repository.\"");
        touch($this->dir . '/test2');
        exec("cd {$this->dir} && git add test2");
        exec("cd {$this->dir} && git commit -m \"Bumping up version.\"");
        exec("cd {$this->dir} && git tag 1.1.0");
    }

    /**
     * @override
     */
    protected function tearDown()
    {
        $this->temp->purgePaths();
    }
}
