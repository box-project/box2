For information on what you do with these settings, please see the documentation for the [build command](#Commands.md#build).

## Table of Contents

- [Example](#example)
- [Settings](#settings)
    - [algorithm](#algorithm)
    - [alias](#alias)
    - [base-path](#basepath)
    - [chmod](#chmod)
    - [compression](#compression)
    - [blacklist](#blacklist)
    - [directories](#directories)
    - [directories-bin](#directories-bin)
    - [files](#files)
    - [files-bin](#files-bin)
    - [finder](#finder)
    - [finder-bin](#finder-bin)
    - [git-version](#gitversion)
    - [key](#key)
        - key-pass
    - [main](#main)
    - [output](#output)
    - [replacements](#replacements)
    - [stub](#stub)

## <a name="example"></a>Example

> This is an example box.json file that uses all of the available settings.

**box.json**

```json
{
    "algorithm": "SHA1",
    "alias": "example.phar",
    "base-path": "/path/to/app",
    "blacklist": [
        "dir/one/a.php",
        "dir/two/b.php"
    ],
    "chmod": "0755",
    "compression": "GZ",
    "directories": [
        "dir/one",
        "dir/two"
    ],
    "directories-bin": [
        "dir/one-assets",
        "dir/two-assets"
    ],
    "files": [
        "file/one.php",
        "file/two.php"
    ],
    "files-bin": [
        "file/one.png",
        "file/two.png"
    ],
    "finder": [
        {
            "name": "*.php",
            "in": "lib/one"
        },
        {
            "name": "*.php",
            "exclude": "Tests",
            "in": "lib/one"
        }
    ],
    "finder-bin": [
        {
            "name": "*.png",
            "in": "lib/one"
        },
        {
            "name": "*.png",
            "exclude": "Tests",
            "in": "lib/one"
        }
    ],
    "git-version": "package_version",
    "key": "dev/private.key",
    "key-pass": true,
    "main": "bin/main.php",
    "output": "example.phar",
    "replacements": {
        "tag1": "Value 1",
        "tag2": "Value 2"
    },
    "stub": "src/stub.php"
}
```

## <a name="settings"></a>Settings

This is a complete list of available settings and their purpose.

### <a name="algorithm"></a>algorithm

The `algorithm` option is used to set what signature algorithm you want to use.  By default, this option is set to **SHA1**.  You may find other available algorithms at the [Phar constants][PharAlgorithms] page.  The value for this option is the class constant name for the algorithm.

### <a name="alias"></a>alias

The `alias` is the name of the stream used for loading files from within the PHAR.  The name of the alias is registered using the [`Phar::mapPhar()`][mapPhar] method when used with the Box generated stub.  The default alias is **default.phar**.

```php
#!/usr/bin/env php
<?php

    Phar::mapPhar('default.phar');

    __HALT_COMPILER();

```

### <a name="basepath"></a>base-path

The value of `base-path` is used to find all of the files referred to by the `files` and `directories` settings.  It is also used to generate the relative paths for the files that have been found, including those found by the `finder` setting.  By default, the directory path to the configuration file is used.

### <a name="blacklist"></a>blacklist

The `blacklist` option is used to prevent one or more files from being added to the PHAR when found using the `directories` or `finder` options.  Each value to the array is a relative path to the file that needs to be blacklisted.

### <a name="chmod"></a>chmod

The `chmod` option is the file permission mode to set the PHAR to after it is created.  The permission mode must be a string containing octal numbers like "0755".  By default, the permission mode is not changed.

### <a name="compression"></a>compression

The `compression` option is used to set what compression algorithm you want to use for the PHAR file.  By default, no compression is used.  You may find other available algorithms at the [Phar constants][PharCompress] page.  The value for this option is the class constant name for the algorithm.

> Warning! If you enable compression, Phar will forcibly override the stub.  This means that the Box generated stub, or the stub file you provide, cannot be used.  If you have a `main` script defined, Box will attempt to work around this by copying it to `index.php`.  If you already have a script called `index.php`, nothing more can be done.

### <a name="directories"></a>directories

The `directories` setting is used to recursively find all files that are in the directory that end with **.php**.  These files will then be added to the PHAR, maintaining their relative directory structure.  Multiple paths can be provided as an array, or just one as a string.  By default, no value is set.

> Note that any files in version control folders, such as .git, will be skipped.

Example directory:

    /path/to/dir/
        .git/
            test.php
        sub1/
            file1.php
            file2.php
            file3.php3
            file4.php4
        sub2/
            file1.jpg
            file2.php
            file3.phtml
            file4.php5

Only these files are added to the PHAR:

    dir/
        sub1/
            file1.php
            file2.php
        sub2/
            file2.php

### <a name="directories-bin"></a>directories-bin

Same as `directories`, but is safe for binary files.

> Note that files added using this setting will be added as-is.  This means that no replacements or white space stripping is performed.

### <a name="files"></a>files

The `files` setting is used to list the files that will be added to the PHAR.  Unlike the `directories` setting, these are paths straight to files, not directories.  These files will maintain their relative directory structure.  Multiple files can be provided as an array, or just one as a string.  By default, no value is set.

### <a name="files-bin"></a>files-bin

Same as `files`, but is safe for binary files.

> Note that files added using this setting will be added as-is.  This means that no replacements or white space stripping is performed.

### <a name="finder"></a>finder

The `finder` setting is used to create one or more instances of the Symfony [Finder][Finder] class, which are then used to add all the files found.  The value of this setting is an array of objects.  Each object lists the methods in the finder class to be called, along with a single value or an array of values  For each value provided, the method is called once.

An example that configures two instances:

```json
{
    "finder": [
        {
            "name": "*.php",
            "exclude": "Tests",
            "in": "src/vendors"
        },
        {
            "name": ["*.php", "*.php5"],
            "in": [
                "src/lib",
                "src/tests"
            ]
        }
    ]
}
```

Is the equivalent of this in PHP:

```php
<?php

    use Symfony\Component\Finder\Finder;

    $finder1 = new Finder;

    $finder1->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->exclude('Tests')
            ->in('src/vendors');

    $finder2 = new Finder;

    $finder2->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->name('*.php5')
            ->in('src/lib')
            ->in('src/tests');

```

> Note that `files()` and `ignoreVCS(true)` are always called.

### <a name="finder-bin"></a>finder-bin

Same as `finder`, but is safe for binary files.

> Note that files added using this setting will be added as-is.  This means that no replacements or white space stripping is performed.

### <a name="gitversion"></a>git-version

The `git-version` setting tells Box that you want a special tag in any of your files to be replaced with the available Git tag or commit hash.  If a tag is available, it will be used over the commit hash.  If no repository is available, the setting is ignored.  By default, no value is set.

This setting

```json
{
    "git-version": "package_version"
}
```

will cause a search and replace of all `@package_version@` occurrences with the current tag or commit hash.  This source code

```php
<?php

    class MyApp
    {
        const VERSION = '@package_version@';
    }

```

becomes this

```php
<?php

    class MyApp
    {
        const VERSION = 'v1.0-RC1';
    }

```

or this

```php
<?php

    class MyApp
    {
        const VERSION = 'e54d16b';
    }

```

> Note that if you already have a replacement set with the same name as the value provided to `git-version`, that value will take precedence over the one retrieved by Box.

### <a name="key"></a>key (and key-pass)

The `key` setting is the file path to your private key, which is used to sign the generated PHAR using OpenSSL.  If you have a password set for the private key, the `key-pass` value is the password to the private key.  Alternatively, you may set the value of `key-pass` to true in order to have Box prompt you for the password.  Keep in mind that prompt will be in clear text, but saves you from setting the password in the configuration file.

### <a name="main"></a>main

The `main` setting is the file path to your main script.  If you are creating a PHAR as a library, the script may simply be an autoloader.  If you are creating an application, this would be the equivalent of your `main()` function in C.

**box.json**

```json
{
    "alias": "hello.phar",
    "files": "Say.php",
    "main": "main.php",
    "output": "hello.phar"
}
```

**Say.php**

```php
<?php

    class Say
    {
        public static function hello()
        {
            echo "Hello, world!\n";
        }
    }
```

**main.php**

```php
<?php

    require 'phar://hello.phar/Say.php';

    Say::hello();
```

**Console**

    $ php box.phar create
    $ php hello.phar
    Hello, world!


### <a name="output"></a>output

The `output` setting is the name of or path to the output file.  If the file already exists, it will be deleted before a new one is generated.  The file path is not absolute, it is relative to the location of the configuration file.

### <a name="replacements"></a>replacements

The `replacements` setting is a list of search and replace tag names and values.  The object key is the name of the tag to find, and the key's value is the value to replace it with.  All keys are wrapped with `@` before performing the search for the tag.

**box.json**

```json
{
    "replacements": {
        "author": "Test Author",
        "name": "world",
    }
}
```

**Say.php**

```php
<?php

    class Say
    {
        const AUTHOR = '@author@';

        public static function hello()
        {
            echo "Hello, @name@!\n";
        }
    }

```

becomes

```php
<?php

    class Say
    {
        const AUTHOR = 'Test Author';

        public static function hello()
        {
            echo "Hello, world!\n";
        }
    }

```

> Note that all occurrences of the tag are replaced in all files that are added to the PHAR.

### <a name="stub"></a>stub

The `stub` setting is the path to the file that will be used as the stub for the PHAR.  Alternatively, you can use the one generated by Box by setting its value to `true`.  If you insist on using your own stub, be sure that it is [properly formatted][Stub].

An example stub generated by Box:

```php
#!/usr/bin/env php
<?php

    /**
     * Generated by Box: http://github.com/kherge/Box
     */

    Phar::mapPhar('default.phar');

    require 'phar://default.phar/bin/main.php';

    __HALT_COMPILER();
```

> Note that the `required`'d file is the main script.

[Finder]: http://symfony.com/doc/current/components/finder.html
[mapPhar]: http://php.net/manual/en/phar.mapphar.php
[PharAlgorithms]: http://php.net/manual/en/phar.constants.php#phar.constants.signature
[PharCompress]: http://php.net/manual/en/phar.constants.php#phar.constants.compression
[Stub]: http://php.net/manual/en/phar.fileformat.stub.php