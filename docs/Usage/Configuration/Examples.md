Here is a collection of example configuration files for a variety of popular PHAR projects.

- [Composer](#composer)
- [Silex](#silex)
- [Slim](#slim)
- [Symfony](#symfony)

## <a name="composer"></a>Composer

```json
{
    "alias": "composer.phar",
    "chmod": "0755",
    "directories": ["src"],
    "files": ["LICENSE"],
    "finder": [
        {
            "name": "*.php",
            "exclude": "Tests",
            "in": "vendor"
        }
    ],
    "git-version": "package_version",
    "main": "bin/composer",
    "output": "composer.phar",
    "stub": true
}
```

## <a name="silex"></a>Silex

> For this example, you will need to copy the stub in [this script](https://github.com/fabpot/Silex/blob/master/src/Silex/Util/Compiler.php) into a file called `stub.php`.  This example will also produce a "fat" Silex PHAR.

```json
{
    "alias": "silex.phar",
    "files": ["LICENSE"],
    "finder": [
        {
            "name": "*.php",
            "notName": "Compiler.php",
            "exclude": ["Tests", "tests", "test-suite"],
            "in": ["src", "vendor"]
        }
    ],
    "output": "silex.phar",
    "stub": "stub.php"
}
```

## <a name="slim"></a>Slim

> Echos `#!` since no custom stub is used.  Autoloading enabled on load.

```json
{
    "alias": "slim.phar",
    "directories": ["Slim"],
    "main": "Slim/Slim.php",
    "output": "Slim.phar",
    "stub": true
}
```

## <a name="symfony"></a>Symfony

> This example is just a "let's see if I can do it" sort of thing.

1. Clone [Symfony](https://github.com/symfony/symfony)
1. Run `composer install --dev`
1. Paste the following configuration into `box.json`

The configuration settings will build a massive PHAR that includes not only the entire Symfony library, but also its dependencies and non-source code assets.  Because of the sheer size of the entire project, it may take a few minutes to build.  You can require the PHAR and the Symfony autoloader will kick in.  There is an issue with the `#!` being echo'd, which can easily be remedied using a custom stub.

```json
{
    "alias": "symfony.phar",
    "finder": [
        {
            "exclude": ["Tests", "Test", "tests", "test"],
            "in": ["src", "vendor"]
        }
    ],
    "main": "autoload.php.dist",
    "output": "symfony.phar",
    "stub": true
}
```