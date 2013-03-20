# Box

Box is a library and command line application for simplifying the PHAR creation process.

## Features

- Create new PHARs with a simple configuration file.
    - Supports whole PHAR compression and signing using a private key.
- Create private keys and extract public keys.
- Add and replace files in existing PHARs.
- Extract existing PHARs, with option to cherry pick files.
- Display PHAR extension and file information.
- Verify PHAR signatures.

## Example

```shell
$ php box.phar build
BUILD Building PHAR...
BUILD Done!
$ php example.phar
Hello, world!
```

Once you have read the available [commands](Commands.md) and [configuration settings](Configuration.md), you may want to check out the [example PHAR project][example].

## Support

Please search the [issue tracker][Issues] before opening a new issue.

[commands]: https://github.com/kherge/Box/wiki/Commands
[config]: https://github.com/kherge/Box/wiki/Configuration
[example]: https://github.com/kherge/BoxExample
[Issues]: https://github.com/kherge/Box/issues