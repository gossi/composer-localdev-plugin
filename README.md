# Composer local Development Plugin

A composer plugin that assists you with local dependencies.

Imagine the following: You are developing two packages, A and B. Package A depends on B. In your normal workflow you would push changes you did in package B to github. .... Then wait.... until changes are pushed to packagist and caches are refreshed. Now you can run `composer update` in package A to get all the changes you did in package B. That's very ineffective, not only do you need to to push changes most importantly you need to wait for a change that is just a directory away. Here is a solution.

## Installation

At best this plugin is installed globally, so go ahead:

```
$ composer global require 'gossi/composer-localdev-plugin=dev-master'
$ composer global update
```

## Usage

The priority in this plugin lies in the truth that you shouldn't change your packages code, it should just work as expected when pushed to github and others want to consume it. You only describe your local development to composer and the rest is handled by this plugin. Open '~/.composer/config.json' and add the `localdev` property to the root node:

```
{
    "config": {
        "localdev": {
            "": ["/path/to/your/packages"],
            "sümfony": "/path/to/sümfony",
			"my/package": "/path/to/my/package"
        }
    }
}
```

As you can see, you can define three types of folder types:

1. Global path: The plugin will look for vendor/package packages in these directories. E.g. if you are looking for `my/pkg` it will look for it in `/path/to/your/packages/my/pkg/`.
2. Vendor path: You can give a path for all packages of a certain vendor. E.g. if you are looking for `sümfony/finda` it will look for it in `/path/to/sümfony/finda/`.
3. Package path: Of course explicitly say where a specific package is located. E.g. the lookup for `my/package` will find it at `/path/to/my/package/`.

For global and vendor packages you can give multiple paths by defining them as an array (see the difference between global and vendors paths in the example above - both are accepted).

### Run update/install

The neat idea is, you don't need to change your workflow at all. If you now run `composer install` or `composer update` in one of your packages, those packages that are available locally (described in the global composer config.json) will be symlinked to their original location. You will see something like the notice.

```
=> Symlinked phootwork/lang from /path/to/phootwork/lang
```

## Pitfalls

At the moment, every package that can be found locally is symlinked no matter what. There must be something to control this, because sometimes you just want your package from packagist in the desired version number. Make your suggestions under [Issue #3](https://github.com/gossi/composer-localdev-plugin/issues/3).

## Issues

There are still some issues, that haven't been solved:

1. Dealing with custom installers: You may have custom installers, that change the installation destination of your package. Normally, the localdev plugin will just wrap them and both work as expected, yet there is a catch. These custom installers (composer plugins as this one) will be installed during the first installation of that required package. They will be unnoticed to the localdev plugin in the first run, so only the custom installer will work. At the moment, you need to run `composer update` one more time to make them both work (See [#1](https://github.com/gossi/composer-localdev-plugin/issues/1)).
2. "Ignore Discard Changes": I run across this issue, yet I don't remember what caused this one. I just keep it here as an reminder, there is something. If you run across this, please open an issue.

## References

- [Composer Development with local Dependencies](http://gos.si/blog/composer-development-with-local-dependencies)
- [Issue #4011 @ composer repository](https://github.com/composer/composer/issues/4011)
- [Plugin Development (composer-dev@googlegroups)](https://groups.google.com/forum/#!topic/composer-dev/u-jKVnuxg2M)

## Contribute

Contributions are welcome, at best open an issue or add a comment to and existing one get the discussion going.
