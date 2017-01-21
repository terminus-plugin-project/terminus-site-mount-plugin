# Site Mount

Terminus plugin to mount Pantheon sites.

## Examples:
Mount the site environment awesome-site.dev.
```
terminus mount awesome-site.dev
```

Unmount the site environment awesome-site.dev.
```
terminus unmount awesome-site.dev
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

## Help
Run `terminus help site:mount` for help.
