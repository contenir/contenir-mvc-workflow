# Upgrading from 1.x to 2.0

Most applications only need the new requirements, plus contenir-metadata's return types
on their resource entity. Workflows that extend the built-in classes, and strategy
subclasses that override `getNavigationPage()`, keep working unchanged.

| | 1.x | 2.0 |
| --- | --- | --- |
| PHP | ^8.1 | 8.3, 8.4 or 8.5 |
| `contenir/contenir-metadata` | ^1.0 | ^2.0 |
| `laminas/laminas-mvc` | ^3.0 | ^3.8 |
| `laminas/laminas-router` | ^3.10 | ^3.13 |
| `laminas/laminas-navigation` | ^2.16 | ^2.19 |
| `laminas/laminas-servicemanager` | (implicit) | ^3.22 |

```bash
composer require contenir/contenir-mvc-workflow:^2.0
```

## 1. contenir-metadata 2.0

Resource entities implementing `Contenir\Metadata\MetadataInterface` must declare
`?string` return types on the string getters. See
[contenir-metadata's upgrade guide](https://github.com/contenir/contenir-metadata/blob/main/UPGRADE-2.0.md).

```php
// 1.x
public function getMetaTitle() { return $this->title; }

// 2.0
public function getMetaTitle(): ?string { return $this->title; }
```

contenir-resource's `BaseResourceEntity` already declares them.

## 2. `WorkflowInterface` return types

Only classes that implement `WorkflowInterface` directly are affected. `AbstractWorkflow`
already declared these types.

```php
// 1.x
final class MyWorkflow implements WorkflowInterface
{
    public function getRouteId() { /* … */ }
    public function getRoutePath() { /* … */ }
    public function getRouteConfig() { /* … */ }
    public function getNavigationConfig() { /* … */ }
}

// 2.0
final class MyWorkflow extends AbstractWorkflow
{
    public function getRouteConfig(): array { /* … */ }
}
```

`ResourceStrategy` has always needed `AbstractWorkflow`'s other methods. In 1.x, a
workflow that only implemented the interface failed with "Call to undefined method".
2.0 throws `Exception\InvalidArgumentException` ("Workflows must extend …") instead.

## 3. `ResourceStrategy`

- **`getCache()` may return `null`.** A strategy without a `cache` option used to fail on
  first use. It now builds the tree once per instance, and `getCache()` returns `null`.

  ```php
  // 1.x
  $strategy->getCache()->removeItem('ResourceStrategyCache');

  // 2.0
  $strategy->getCache()?->removeItem('ResourceStrategyCache');
  ```

  Subclasses overriding `getCache()` must declare `?StorageInterface`.
- **`getNavigationSubPage()`** (protected) takes `ResourceInterface $resource` instead of
  `object $resource`. Overrides that keep `object` remain compatible.
- **The constructor** assigns the plugin manager and repository directly. Subclasses that
  override `setPluginManager()` or `setRepository()` are no longer called from the
  constructor.
- **Unknown options** throw `Contenir\Mvc\Workflow\Exception\InvalidArgumentException`.
  It extends `\InvalidArgumentException`, so existing `catch` blocks still match.
- **Children** must implement `ResourceInterface`. Anything else throws
  `Exception\InvalidArgumentException` (it was a `TypeError`).
- **Sub-pages' `visible`** is a boolean, as top-level pages' already was. laminas-navigation
  casts it the same way, so menus are unchanged.

## 4. Factories

| Factory | 1.x | 2.0 |
| --- | --- | --- |
| `PluginManagerFactory::__invoke()` | `Interop\Container\ContainerInterface $container` (broken on current laminas-servicemanager) | `Psr\Container\ContainerInterface $container` |
| `WorkflowFactory::__invoke()` | returns `object`, instantiates any class | returns `AbstractWorkflow`; other classes throw `Exception\InvalidArgumentException` |
| `ResourceStrategyFactory::__invoke()` | `ResourceStrategyInterface::class` failed (cannot instantiate an interface) | builds a `ResourceStrategy` for the interface |
| `ResourceStrategyFactory` `cache` option | always resolved from the container | a service name is resolved; an instance is used as is; `null`/`''` means no cache |

## 5. Wiring classes are `final`

`Module`, `ConfigProvider`, `PluginManagerFactory`, `Strategy\ResourceStrategyFactory`
and `Workflow\WorkflowFactory` are now `final`. Extend nothing from them. To change
how a service is built, register your own factory for it:

```php
// 1.x
class MyStrategyFactory extends ResourceStrategyFactory { /* … */ }

// 2.0
final class MyStrategyFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName): MyStrategy
    {
        $strategy = (new ResourceStrategyFactory())($container, MyStrategy::class);
        // … adjust $strategy …
        return $strategy;
    }
}
```

Classes meant for extension stay open: the workflows, `ResourceStrategy`,
`WorkflowNavigationFactory` (sites subclass it to set `$name`), `PluginManager` and the
exceptions.

## 6. Exceptions instead of errors

| Situation | 1.x | 2.0 |
| --- | --- | --- |
| `getWorkflowId()` before `setResource()` | `TypeError` | `Exception\RuntimeException` |
| `getRouteController()` without `$controller` | `TypeError` | `Exception\RuntimeException` |
| `getRoutePath()` / built-in `getRouteConfig()` without a resource | `Error` | `Exception\RuntimeException` |
| `getResourceId()` without `$controller` | deprecation, `controller:` | `controller:` |
| `workflow_manager.strategy.type` missing | warning, then `TypeError` | `Exception\InvalidArgumentException` |
| Strategy or router service of the wrong type | `Error` | `Exception\InvalidArgumentException` |

`Exception\RuntimeException` is new, and implements `Exception\ExceptionInterface`
like `Exception\InvalidArgumentException`.

Projects that must stay on PHP 8.1 or 8.2 can keep using `^1.0`, which is
maintained on the `1.x` branch.
