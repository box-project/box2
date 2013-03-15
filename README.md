# Box

[![Build Status](https://secure.travis-ci.org/kherge/Box.png?branch=master)](http://travis-ci.org/kherge/Box)

An application for building and managing Phars.

## Installation

To install, you need to run this command in your shell:

```sh
$ curl -s http://box-project.org/installer.php | php
```

Updates can later be performed by running Box's update command:

```sh
$ php box.phar update
```

You may also use Composer:

```sh
$ composer require kherge/box=~2.0
```

## Usage

To build a new PHAR using Box, [configure][configure] your project

**box.json**:

```json
{
    "directories": [
        "/path/to/source",
        "/path/to/source"
    ]
}
```

and run

```sh
$ php box.phar build
```

You can also check out the [example PHAR application][example] ready to be build by Box.

Please see [the wiki][wiki] for more detailed usage information.

[configure]: https://github.com/kherge/Box/wiki/Configuration
[example]: https://github.com/kherge/BoxExample
[wiki]: https://github.com/kherge/Box/wiki