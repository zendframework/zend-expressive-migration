# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 0.1.2 - 2018-03-15

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixes an issue that occurs when running in an application that does not
  require zend-diactoros in the package root. The tool now explicitly requires it
  before doing any other migrations.

- Adds a default directory in which to convert middleware to request handlers.

- Adds logic to remove the tool package itself during migration, ensuring it is
  not present in the final artifacts.

## 0.1.1 - 2018-03-15

### Added

- Nothing.

### Changed

- [#3](https://github.com/zendframework/zend-expressive-migration/pull/3)
  updates the list of expected versions in the README to reflect released
  versions.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#4](https://github.com/zendframework/zend-expressive-migration/pull/4)
  fixes some Windows compatibility problems.

- [#5](https://github.com/zendframework/zend-expressive-migration/pull/5)
  fixes some minor issues in detecting the latest version of the skeleton, as
  well as retrieving content from the skeleton.

- [#5](https://github.com/zendframework/zend-expressive-migration/pull/5)
  updates the tool to remove any "minimum-stability" settings in the
  `composer.json`.

## 0.1.0 - 2018-03-14

Initial functionality for migrating to version 3.

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
