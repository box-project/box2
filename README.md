# Box

[![Build Status](https://secure.travis-ci.org/kherge/Box.png?branch=master)](http://travis-ci.org/kherge/Box)

Box is a library and command line application for simplifying the PHAR creation process.

## Installation

### As an application

You may download the PHAR from the [downloads page][downloads].

### As a library

1. Make sure you have [Composer][Composer] installed.
2. Add Box to your list of requirements:

    $ php composer.phar require kherge/box=1.0.*

## Configuration

Please see [this guide][guide] for configuring your application's build process.

## Usage

To create a PHAR

    $ php box.phar create

(alternatively)

    $ php box.phar create --config /path/to/box.json

To verify a PHAR

    $ php box.phar verify myPhar.phar

[Composer]: http://getcomposer.org/
[downloads]: https://github.com/kherge/Box/downloads
[guide]: https://github.com/kherge/Box/wiki/Configuration
