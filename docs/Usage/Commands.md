## Available Commands

- [add](#add)
- [build](#build)
- [extract](#extract)
- [info](#info)
- [remove](#remove)
- [update](#update)
- [validate](#validate)
- [verify](#verify)
- Private Key
    - [openssl:create-private](#priv-create)
    - [openssl:extract-public](#priv-extract)

#### <a name="add"></a>add

> Requires that the `phar.readonly` PHP INI setting be disabled (i.e. set to `0`).

```
add [-b|--bin] [-c|--configuration="..."] [-m|--main] [-r|--replace] [-s|--stub] phar external [internal]
```

The `add` command will add, or replace an existing, file in the PHAR.

The `external` argument is the file path to the file.  The `internal` argument is the file path inside the PHAR to store the file in, and required by all scenarios except for when using the `--stub` option.

- To replace an existing file, you need to use the `--replace` option.
- To add a binary file, you will need to use the `--bin` option to prevent the application from potentially damaging the file data.
- To replace a main script, you will want to use the `--main` option to treat it as a main script file.
- To replace the stub, you will need to use the `--stub` option.

### <a name="build"></a>build

> Requires that the `phar.readonly` PHP INI setting be disabled (i.e. set to `0`).

```
build [-c|--configuration="..."]
```

The `build` command is used to create a new PHAR.

The command will use the [[configuration file|Configuration]] to create the new PHAR.  The configuration file used is either one you specified (using `--configuration`), `box.json`, or `box.dist.json` in that order.  By default, you will only see the build start and build end messages.  However, by using the `--verbose` option, you may see additional messages that better describe the build process.

### <a name="extract"></a>extract

> Requires that the `phar.readonly` PHP INI setting be disabled (i.e. set to `0`).

```
extract [-o|--out="..."] [-w|--want="..."] phar
```

The `extract` command will extract the contents of a `PHAR` file.

The command will extract the contents of the `phar` to a directory called `PHAR-contents`, where `PHAR` is the name of the PHAR file.  You may specify an alternative output directory by using the `--out` option.  You may also cherry pick what files you need by using the `--want` option one or more times.

### <a name="info"></a>info

```
info [-l|--list] [phar]
```

The `info` command displays the PHAR extension information: current version, supported compression algorithms, and supported signature algorithms.

If `phar` is given, the PHAR's information will be displayed: API version, compression algorithm used (if any), signature algorithm used.  If the `--list` option is used, the command will also display the list of files and directories inside the PHAR.

### <a name="remove"></a>remove

> Requires that the `phar.readonly` PHP INI setting be disabled (i.e. set to `0`).

```
remove phar internal1 ... [internalN]
```

The `remove` command will remove one or more files from a PHAR.

The `internal` argument is the internal path of the PHAR to the file.  You may specify multiple file paths to delete more than one file at a time.

> NOTE: The command will not work with directories.

### <a name="update"></a>update

```
update [-u|--upgrade] [-r|--redo]
```

The `update` command will update the Box application.

The command will only update itself within the same major version.  To upgrade the application, you will need to use the `--upgrade` option.  If the application is already current, you may force a re-download using the `--redo` option.

### <a name="validate"></a>validate

```
validate [configuration]
```

The `validate` command will validate the configuration file.

> NOTE: The way the configuration file is found is similar to that of the `build` command.  The difference being that the alternative configuration file path is an argument, not an option.

The validate command checks for invalid syntax, and also for invalid configuration settings.  By default, only a pass or fail message is displayed.  To view details about invalid configuration files, you will need to use the `--verbose` option.

### <a name="verify"></a>verify

```
verify phar
```

The `verify` command verifies the `phar`'s signature.

If the `phar` has been signed using a private key, you will need to have the `openssl` extension installed and available.  Also be sure to have the `phar`'s public key in the same directory with the appropriate name name (`phar`.pubkey).

### Private Key

Box includes a couple of simple commands to create private keys and extract their public keys.

#### <a name="priv-create"></a>openssl:create-private

```bash
openssl:create-private [-b|--bits="..."] [-o|--out="..."] [-p|--prompt] [-t|--type="..."]
```

The `openssl:create-private` command will create a new private key.

By default, the command will create a generate an RSA 1024-bit key in PEM format and save it to a file called `private.key` in the current working directory.  To change the key type, you may use the `--type` option.  To change the key size by using the `--bits` option.  You may also change where the generated private key is saved to by using the `--out` option.

If `--prompt` is used, the command will prompt for a passphrase for the new private key.

#### <a name="priv-extract"></a>openssl:extract-public

```bash
openssl:extract-public [-o|--out="..."] [-p|--prompt] private
```

The `openssl:extract-public` command will extract the public key from an existing `private` key.

If a password is required to use the private key, you will need to use the `--prompt` option.  By default, the public key is extracted to `public.key`.  You may specify an alternative file path by using the `--out` option.