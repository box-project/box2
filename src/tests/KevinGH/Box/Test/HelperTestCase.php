<?php

/* This file is part of Box.
 *
 * (c) 2012 Kevin Herrera
 *
 * For the full copyright and license information, please
 * view the LICENSE file that was distributed with this
 * source code.
 */

namespace KevinGH\Box\Test;

use Symfony\Component\Console\Helper\HelperInterface;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * A test case for Box helpers.
 *
 * @author Kevin Herrera <me@kevingh.com>
 */
class HelperTestCase extends TestCase
{
    /**
     * The helper.
     *
     * @var HelperInterface
     */
    protected $helper;

    /**
     * The helper class.
     *
     * @var string
     */
    protected $helperClass;

    /**
     * The helper set.
     *
     * @var HelperSet
     */
    protected $helperSet;

    /** {@inheritDoc} */
    protected function setUp()
    {
        parent::setUp();

        $this->helperSet = $this->createHelperSet();
        $this->helper = $this->createHelper();

        $this->helperSet->set($this->helper);
        $this->helper->setHelperSet($this->helperSet);
    }

    /**
     * Creates the helper to be tested.
     *
     * @return HelperInterface The helper.
     */
    protected function createHelper()
    {
        return new $this->helperClass();
    }

    /**
     * Creates the helper set.
     *
     * @return HelperSet The helper set.
     */
    protected function createHelperSet()
    {
        return new HelperSet();
    }
}

