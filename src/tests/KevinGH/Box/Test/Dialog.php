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

    use Symfony\Component\Console\Helper\DialogHelper,
        Symfony\Component\Console\Output\OutputInterface;

    /**
     * A dialog with a fixed return value.
     *
     * @author Kevin Herrera <me@kevingh.com>
     */
    class Dialog extends DialogHelper
    {
        /**
         * The return value.
         *
         * @type mixed
         */
        private $value;

        /** {@inheritDoc} */
        public function ask(OutputInterface $output, $question, $default = null)
        {
            return $this->value;
        }

        /**
         * Sets the return value.
         *
         * @param mixed $value The return value.
         */
        public function setReturn($value)
        {
            $this->value = $value;
        }
    }