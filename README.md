Box
===

[![Build Status](https://travis-ci.org/box-project/box2.svg?branch=2.0)](https://travis-ci.org/box-project/box2)

What is it?
-----------

The Box application simplifies the Phar building process. Out of the box (no pun intended), the application can do many great things:

- Add, replace, and remove files and stubs in existing Phars.
- Extract a whole Phar, or cherry pick which files you want.
- Retrieve information about the Phar extension, or a Phar file.
  - List the contents of a Phar.
- Verify the signature of an existing Phar.
- Generate RSA (PKCS#1 encoded) private keys for OpenSSL signing.
  - Extract public keys from existing RSA private keys.
- Use Git tags and short commit hashes for versioning.

Since the application is based on the [Box library](https://github.com/herrera-io/php-box), you get its benefits as well:

- On the fly search and replace of placeholders.
- Compact file contents based on file type.
- Generate custom stubs.

How do I get started?
---------------------

You can use Box in one of three ways:

### As a Phar (Recommended)

You may download a ready-to-use version of Box as a Phar:

```sh
$ curl -LSs https://box-project.github.io/box2/installer.php | php
```

The command will check your PHP settings, warn you of any issues, and the download it to the current directory. From there, you may place it anywhere that will make it easier for you to access (such as `/usr/local/bin`) and chmod it to `755`. You can even rename it to just `box` to avoid having to type the `.phar` extension every time.

```sh
$ box --version
```

Whenever a new version of the application is released, you can simply run the `update` command to get the latest version:

```sh
$ box update
```

### As a Global Composer Install

This is probably the best way when you have other tools like phpunit and other tools installed in this way:

```sh
$ composer global require kherge/box --prefer-source
```

### As a Composer Dependency

You may also install Box as a dependency for your Composer managed project:

```sh
$ composer require --dev kherge/box
```

(or)

```json
{
    "require-dev": {
        "kherge/box": "~2.5"
    }
}
```

> Be aware that using this approach requires additional configuration steps to prevent Box's own dependencies from accidentally being added to your Phar, causing file size bloat. You can find more information about this [issue on the wiki](https://github.com/kherge/php-box/wiki/App%2C-or-Library%3F).

Once you have installed the application, you can run the `help` command to get detailed information about all of the available commands. This should be your go-to place for information about how to use Box. You may also find additional useful information [on the wiki](https://github.com/kherge/php-box/wiki). If you happen to come across any information that could prove to be useful to others, the wiki is open for you to contribute.

```sh
$ box help
```

Creating a Phar
---------------

To get started, you may want to check out the [example application](https://github.com/kherge/php-box-example) that is ready to be built by Box. How your project is structured is entirely up to you. All that Box requires is that you have a file called `box.json` at the root of your project directory. You can find a complete and detailed list of configuration settings available by seeing the help information for the `build` command:

```sh
$ box help build
```

> You may find example configuration files for popular projects on the wiki.

Once you have configured your project using `box.json` (or `box.json.dist`), you can simply run the `build` command in the directory containing `box.json`:

```sh
$ box build -v
```

> The `-v` option enabled verbose output. This will provide you with a lot of useful information for debugging your build process. Once you are satisfied with the results, I recommend not using the verbose option. It may considerably slow down the build process.

Contributing
------------

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/kherge/php-box/issues).
2. Answer questions or fix bugs on the issue tracker.
3. Contribute new features or update the wiki.

> The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable.

GPG Signature
-------------

You can download Kevin Herrera's public key and verify the signature (`box.phar.sig`) of the `box.phar`.

    gpg --keyserver hkp://pgp.mit.edu --recv-keys 41515FE8
    gpg --verify box.phar.sig box.phar
