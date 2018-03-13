# zend-expressive-migration

[![Build Status](https://secure.travis-ci.org/webimpress/zend-expressive-migration.svg?branch=master)](https://secure.travis-ci.org/webimpress/zend-expressive-migration)
[![Coverage Status](https://coveralls.io/repos/github/webimpress/zend-expressive-migration/badge.svg?branch=master)](https://coveralls.io/github/webimpress/zend-expressive-migration?branch=master)

This library provides tool to migrate from Expressive v2 to v3.

## Installation

Run the following to install this library:

```console
$ composer require --dev webimpress/zend-expressive-migration
```

## How to use this tool

If you've installed `webimpress/zend-expressive-migration` using composer you
can run migration script in your expressive v2 application:

```console
$ vendor/webimpress/zend-expressive-migration/bin/expressive-migration migrate
```

You can also clone repository to separate directory:

```console
$ git clone https://github.com/webimpress/zend-expressive-migration
```

and run script from your expressive v2 application:

```console
$ path/to/zend-expressive-migration/bin/expressive-migration migrate
```

> **TODO:**
>
> Our goal is to prepare phar library with migration tool.

## Requirements

All external packages used within your project must be compatible with expressive v3 libraries.

Script will uninstall all dependent packages and then will try to install them with the latest
compatible version. In case there is any package not compatible it will report an error, which
package needs to be updated.

Here is the list of `Zend Framework` expressive packages:

| Package name                                      | Version |
| ------------------------------------------------- | ------- |
| zend-auradi-config                                | 1.0.0   |
| zend-component-installer                          | 2.1.0   |
| zend-config-aggregator                            | 1.1.0   |
| zend-diactoros                                    | 1.7.1   |
| zend-expressive                                   | 3.0.0   |
| zend-expressive-aurarouter                        | 3.0.0   |
| zend-expressive-authentication                    | 1.0.0   |
| zend-expressive-authentication-basic              | 1.0.0   |
| zend-expressive-authentication-oauth2             | 1.0.0   |
| zend-expressive-authentication-session            | 1.0.0   |
| zend-expressive-authentication-zendauthentication | 1.0.0   |
| zend-expressive-authorization                     | 1.0.0   |
| zend-expressive-authorization-acl                 | 1.0.0   |
| zend-expressive-authorization-rbac                | 1.0.0   |
| zend-expressive-csrf                              | 1.0.0   |
| zend-expressive-fastroute                         | 3.0.0   |
| zend-expressive-flash                             | 1.0.0   |
| zend-expressive-hal                               | 1.0.0   |
| zend-expressive-helpers                           | 5.0.0   |
| zend-expressive-platesrenderer                    | 2.0.0   |
| zend-expressive-router                            | 3.0.0   |
| zend-expressive-session                           | 1.0.0   |
| zend-expressive-session-ext                       | 1.0.0   |
| zend-expressive-template                          | 2.0.0   |
| zend-expressive-tooling                           | 1.0.0   |
| zend-expressive-twigrenderer                      | 2.0.0   |
| zend-expressive-zendrouter                        | 3.0.0   |
| zend-expressive-zendviewrenderer                  | 2.0.0   |
| zend-httphandlerrunner                            | 1.0.1   |
| zend-pimple-config                                | 1.0.0   |
| zend-problem-details                              | 1.0.0   |
| zend-stratigility                                 | 3.0.0   |


## What that tool actually is doing?

There must be `composer.json` in your application directory
and the file must be writable by the script. 

First we try to detect currently used expressive version.
If detected version is not 2.X script exits and no any action
will be preformed.

**Then magic begins...**

1. Removes `vendor` directory.

2. Installs current dependencies: `composer install`.

3. Analyzes composer.lock to find all packages which depends on expressive packages.

4. Removes all installed expressive packages and packages which depends on them.

5. Updates all remaining packages: `composer update`.

6. Requires all expressive packages previously installed
  (packages `zendframework/zend-component-installer` and `zendframework/zend-expressive-tooling` will be added to `require-dev` section even if these were not installed before).

7. Requires all dependent packages installed previously
  (this step may fail in case some of external packages are not compatible with Expressive v3).

8. Updates `config/pipeline.php`:
   a. adds function wrapper;
   b. adds strict type declaration on top of the file;
   c. updates middlewares:
      - `pipeRoutingMiddleware` (or `Zend\Expressive\Router\Middleware\RouteMiddleware` from Expressive 2.2) to `Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware`,
      - `pipeDispatchMiddleware` to `Zend\Expressive\Router\Middleware\DispatchMiddleware`,
      - `Zend\Expressive\Middleware\NotFoundHandler` to `Zend\Expressive\Handler\NotFoundHandler`,
      - `Zend\Expressive\Middleware\ImplicitHeadMiddleware` to `Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware`,
      - `Zend\Expressive\Middleware\ImplicitOptionsMiddleware` to `Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware`,

   d. pipes `Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware` after `Implicit*Middleware` (or if these are not piped after `Zend\Expressive\Router\Middleware\PathBasedRoutingMiddleware`).

9. Updates `config/routes.php`:
   a. adds function wrapper;
   b. adds strict type declaration on top of the file.

10. Replaces `public/index.php` with the latest version from skeleton.

11. Updates container configuration if `pimple` or `Aura.Di` were used (`config/container.php`) from the latest skeleton version:
    - `pimple`: package `xtreamwayz/pimple-container-interop` is replaced by `zendframework/zend-pimple-config`;
    - `Aura.Di`: package `zendframework/zend-auradi-config` is installed.

12. Migrates interop middlewares to PSR-11 middlewares
  (script asks to provide path to source directory, default `src`).

13. Migrates middlewares to handler requests (only if delegator is not used)
  (script asks to provide path to action middlewares).

14. Runs CS aut-fixer if script `vendor/bin/phpcbf` is available.

15. DONE!

## What to do after migration?

You need update your tests to use PSR-11 middlewares instead of interop middlewares.
This step is not done automatically because _it is too complicated_.
We can easily change imported classes but unfortunately it's really hard to find all `handle`
usages.

Please compare diff to manually verify all changes. It is possible that in some
edge case script is not going to work correctly. Depends how many modifications to
the original skeleton you have provided.

You can also import classes and improve formatting in files
`config/pipeline.php` and `config/routes.php`.

> NOTE:
>
> Script does not work currently with Application delegator.
