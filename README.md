# Terminus Site Mount Plugin

Terminus plugin to mount Pantheon sites.

By default, the site environment will be mounted in `/tmp/site-name.dev`.

If you want to mount in a different directory, use the `--dir=<directory>` option.

Keep in mind, if you do mount in a different directory, you will also need to specific the same `--dir` option when unmounting.

## Examples:
Mount the site environment awesome-site.dev.
```
terminus mount awesome-site.dev [--dir=<directory>]
```

Unmount the site environment awesome-site.dev.
```
terminus unmount awesome-site.dev [--dir=<directory>]
```

Learn more about [Terminus](https://pantheon.io/docs/terminus/) and [Terminus Plugins](https://pantheon.io/docs/terminus/plugins/).

## Prerequisites:

Executable mount, umount and sshfs commands must exist.

## Installation:
For installation help, see [Manage Plugins](https://pantheon.io/docs/terminus/plugins/).

```
mkdir -p ~/.terminus/plugins
composer create-project -d ~/.terminus/plugins terminus-plugin-project/terminus-site-mount-plugin:~1
```

## Configuration:

This plugin requires no configuration to use.

## Testing:

Replace `my-test-site` with the site you want to test:
```
TERMINUS_SITE=my-test-site
cd ~/.terminus/plugins/terminus-site-mount-plugin
composer install
composer test
```

## Help:
Run `terminus help site:mount` for help.
