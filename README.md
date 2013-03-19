# Box

[![Build Status](https://travis-ci.org/kherge/Box.png?branch=1.0)](https://travis-ci.org/kherge/Box)

Box is a library and command line application for simplifying the PHAR creation process.

## Installation

To install, you need to run this command in your shell:

    $ curl -s http://box-project.org/installer.php | php

Updates can later be performed by running Box's update command:

    $ php box.phar update

## Usage

To build a new PHAR using Box, [configure][configure] your project

**box.json**:

    {
        "directories": [
            "/path/to/source",
            "/path/to/source"
        ]
    }

and run

    $ php box.phar build

You can also check out the [example PHAR application][example] ready to be build by Box.

Please see [the wiki][wiki] for more detailed usage information.

[configure]: https://github.com/kherge/Box/wiki/Configuration
[example]: https://github.com/kherge/BoxExample
[wiki]: https://github.com/kherge/Box/wiki
