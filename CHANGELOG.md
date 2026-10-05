# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [2.0.0] - Unreleased

PHP 8.3+, contenir-metadata 2.x, native types on `WorkflowInterface`, and fixes
for strategies without a cache and for the default service wiring. See
[UPGRADE-2.0.md](UPGRADE-2.0.md) for every break.

### Changed

- `Module`, `ConfigProvider`, `PluginManagerFactory`, `ResourceStrategyFactory` and
  `WorkflowFactory` are `final`.
- Requires PHP 8.3, 8.4 or 8.5, and `contenir/contenir-metadata` ^2.0.
- `WorkflowInterface` declares native return types: `getRouteId(): string`,
  `getRoutePath(): string`, `getRouteConfig(): array`, `getNavigationConfig(): array`.
- `ResourceStrategy::getCache()` returns `?StorageInterface`.
- `ResourceStrategy::getNavigationSubPage()` takes a `ResourceInterface` rather than any object.
- `WorkflowFactory::__invoke()` returns `AbstractWorkflow`, and rejects other classes.
- `PluginManagerFactory::__invoke()` takes a PSR-11 container.
- Misuse now throws the package's exceptions instead of PHP errors:
  - a workflow without a resource or controller throws `Exception\RuntimeException`;
  - a workflow plugin that does not extend `AbstractWorkflow`, or a child resource that is
    not a `ResourceInterface`, throws `Exception\InvalidArgumentException`;
  - an unknown strategy option throws `Exception\InvalidArgumentException`, which extends
    the SPL exception thrown before.
- `Module::onBootstrap()` and `WorkflowNavigationFactory` validate the strategy type and
  the service types.
- Navigation sub-pages carry `visible` as a boolean, like top-level pages.
- Resource properties (`title`, `visible`, `children`, ...) are read null-safely, so a
  resource without one no longer raises warnings.
- Interfaces and extendable classes are documented as `@api`.

### Fixed

- `PluginManagerFactory` type-hinted the removed `Interop\Container\ContainerInterface`,
  so it failed with a `TypeError` on current laminas-servicemanager.
- `ResourceStrategy` without a `cache` option failed with "must not be accessed before
  initialization". It now builds the tree once per instance.
- `ResourceStrategyFactory` failed with a `TypeError` when the strategy had no `options` key.
- The default `workflow_strategy` service (`ResourceStrategyInterface`) tried to
  instantiate the interface. It now builds a `ResourceStrategy`.
- An empty or null `cache` option is ignored, and a `StorageInterface` instance is used as is.
- `PluginManagerFactory` and `WorkflowFactory` raised undefined-key warnings when
  `workflow_manager` or `workflow` was not configured (as with `ConfigProvider`).
- `AbstractWorkflow::getResourceId()` raised a deprecation for workflows without a controller.
- `ResourceStrategy` treats an unreadable cache item as a miss, rather than failing.

### Added

- `Exception\RuntimeException`.
- Continuous integration on PHP 8.3, 8.4 and 8.5 against lowest, locked and latest
  dependencies, with coverage reported to Codecov.
- Unit and integration test suites with 100% line and branch coverage, `docs/` and an
  upgrade guide.
- `LICENSE.md` with the BSD-3-Clause text the package was already declared under.

### Removed

- `laminas/laminas-coding-standard`, `phpcs.xml` and the 1.x PHPUnit configuration,
  replaced by Mago via `php-db/phpdb-qa-tools` and `phpunit.xml.dist`.

## [1.0.7] - 2026-05-18

- Hidden resources are skipped when building the navigation tree.
- Test suite.

## [1.0.6] - 2024-10-26 to [1.0.6.2] - 2024-10-27

- Refactored for PHP 8.1.
- Added `AbstractArticleWorkflow`.

## [1.0.5] - 2023-12-15 to [1.0.5.2] - 2024-10-19

- Dropped PHP 7.x; PHP 8.3 compatibility.
- Child routes are added for landing pages.

## [1.0.4] - 2023-12-15

- Fixed workflow route sub-pages.

## [1.0.3] - 2023-04-24 to [1.0.3.2] - 2023-12-13

- Added `ArticleWorkflow`, with page resource handling.
- `lastmod` is formatted as a string.

## [1.0.2] - 2022-07-04

- Better handling of workflow configuration.

## [1.0.1] - 2022-04-28

- Fixed incorrect route paths.

## [1.0.0] - 2021-12-05

- Initial release.
