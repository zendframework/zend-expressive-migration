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

Then magic begins...
