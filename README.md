# zend-expressive-migration

[![Build Status](https://secure.travis-ci.org/zendframework/zend-expressive-migration.svg?branch=master)](https://secure.travis-ci.org/zendframework/zend-expressive-migration)
[![Coverage Status](https://coveralls.io/repos/github/zendframework/zend-expressive-migration/badge.svg?branch=master)](https://coveralls.io/github/zendframework/zend-expressive-migration?branch=master)

This library provides a tool for migrating from Expressive v2 to v3.

## Installation

Run the following to install this library:

```console
$ composer require --dev zendframework/zend-expressive-migration
```

## Usage

Once you have installed the tool, execute it with the following:

```bash
$ ./vendor/bin/expressive-migration migrate
```

> ### Cloning versus composer installation
>
> If you'd rather clone the tooling once and re-use it many times, you can do
> that instead. Clone using:
>
> ```bash
> $ git clone https://github.com/zendframework/zend-expressive-migration
> ```
>
> And then, instead of using `./vendor/bin/expressive-migration migrate`, use
> `/full/path/to/zend-expressive-migration/bin/expressive-migration`.

> **TODO:**
>
> Our goal is to prepare a downloadable [phar](http://php.net/phar) file that
> can be installed in your system and re-used; this change will come at a future
> date.

## Requirements

All external packages used within your project must be compatible with
Expressive v3 libraries. If you are unsure, check their dependencies.

This script will uninstall all dependent packages and then will try to install
them with the latest compatible version. In case any package is not compatible,
the script will report an error indicating which package need to be updated.

The following table indicates Expressive package versions compatible with
version 3, and to which the migration tool will update.

| Package name                                      | Version |
| ------------------------------------------------- | ------- |
| zend-auradi-config                                | 1.0.0   |
| zend-component-installer                          | 2.1.0   |
| zend-config-aggregator                            | 1.1.0   |
| zend-diactoros                                    | 1.7.1   |
| zend-expressive                                   | 3.0.0   |
| zend-expressive-aurarouter                        | 3.0.0   |
| zend-expressive-authentication                    | 0.4.0   |
| zend-expressive-authentication-basic              | 0.3.0   |
| zend-expressive-authentication-oauth2             | 0.4.0   |
| zend-expressive-authentication-session            | 0.4.0   |
| zend-expressive-authentication-zendauthentication | 0.4.0   |
| zend-expressive-authorization                     | 0.4.0   |
| zend-expressive-authorization-acl                 | 0.3.0   |
| zend-expressive-authorization-rbac                | 0.3.0   |
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


## What does the tool do?

In order to operate, the tool requires that the application directory contains a
`composer.json` file, and that this file is writable by the script.

Next, it attempts to detect the currently used Expressive version. If the
version detected is not a 2.X version, the script will exit without performing
any changes.

It then performs the following steps:

1. Removes the `vendor` directory.

2. Installs current dependencies using `composer install`.

3. Analyzes `composer.lock` to identify all packages which depends on Expressive packages.

4. Removes all installed Expressive packages and packages that depend on them.

5. Updates all remaining packages using `composer update`.

6. Requires all Expressive packages previously installed, adding the packages
   `zendframework/zend-component-installer` and `zendframework/zend-expressive-tooling`
   as development packages if they were not previously installed.

7. Requires all packages installed previously that were dependent on Expressive.
   **This step may fail** in situations where external packages are not yet
   compatible with Expressive v3 or its required libraries.

8. Updates configuration-driven pipelines
   1. updates the following middleware:
      - `Zend\Expressive\Application::ROUTING_MIDDLEWARE`/`Zend\Expressive\Container\ApplicationFactory::ROUTING_MIDDLEWARE`
        becomes `Zend\Expressive\Router\Middleware\RouteMiddleware::class`.
      - `Zend\Expressive\Application::DISPATCH_MIDDLEWARE`/`Zend\Expressive\Container\ApplicationFactory::DISPATCH_MIDDLEWARE`
        becomes `Zend\Expressive\Router\Middleware\DispatchMiddleware::class`.
      - References to `Zend\Expressive\Middleware\NotFoundHandler` become `Zend\Expressive\Handler\NotFoundHandler`.
      - References to `Zend\Expressive\Middleware\ImplicitHeadMiddleware` become `Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware`.
      - References to `Zend\Expressive\Middleware\ImplicitOptionsMiddleware` become `Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware`.
   2. pipes `Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware` after
      `Implicit*Middleware` (or if these are not piped, after
      `Zend\Expressive\Router\Middleware\RouteMiddleware`).

9. Updates `config/pipeline.php`:
   1. adds strict type declarations to the top of the file;
   2. adds a function wrapper (as is done in the version 3 skeleton);
   3. updates the following middleware:
      - `pipeRoutingMiddleware` becomes a `pipe()` statement referencing `Zend\Expressive\Router\Middleware\RouteMiddleware`.
      - `pipeDispatchMiddleware` becomes a `pipe()` statement referencing `Zend\Expressive\Router\Middleware\DispatchMiddleware`.
      - References to `Zend\Expressive\Middleware\NotFoundHandler` become `Zend\Expressive\Handler\NotFoundHandler`.
      - References to `Zend\Expressive\Middleware\ImplicitHeadMiddleware` become `Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware`.
      - References to `Zend\Expressive\Middleware\ImplicitOptionsMiddleware` become `Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware`.
   4. pipes `Zend\Expressive\Router\Middleware\MethodNotAllowedMiddleware` after
      `Implicit*Middleware` (or if these are not piped, after
      `Zend\Expressive\Router\Middleware\RouteMiddleware`).

10. Updates `config/routes.php`:
   1. adds strict type declaration on top of the file;
   2. adds a function wrapper (as is done in the version 3 skeleton).

11. Replaces `public/index.php` with the latest version from the v3 skeleton.

12. Updates container configuration if `pimple` or `Aura.Di` were used
    (`config/container.php`) from the latest skeleton version. Additionally, it
    does the following:
    - For `pimple`: the package `xtreamwayz/pimple-container-interop` is replaced by `zendframework/zend-pimple-config`.
    - For `Aura.Di`: the package `aura/di` is replaced by `zendframework/zend-auradi-config`.

13. Migrates http-interop middleware to PSR-15 middleware using
    `./vendor/bin/expressive migrate:interop-middleware`.

14. Migrates PSR-15 middleware to PSR-15 request handlers using
    `./vendor/bin/expressive migrate:middleware-to-request-handler`.

15. Runs `./vendor/bin/phpcbf` if it is available.

## What should you do after migration?

You will need to update your tests to use PSR-15 middleware instead of
http-interop middleware.  This step is not done automatically because _it is too
complicated_. We can easily change imported classes, but unfortunately test
strategies and mocking strategies vary widely, and detecting all http-interop
variants makes this even more difficult.

Please manually compare and verify all changes made. It is possible that in some
edge cases, the script will not work correctly. This will depend primarily on
the number of modifications you have made to the original skeleton.
