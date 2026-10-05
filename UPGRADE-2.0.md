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
composer require contenir/contenir-workflow-laminas-mvc:^2.0
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

## 5. Every concrete class is `final`

Extension points are abstract classes and interfaces. Wiring classes (`Module`,
`ConfigProvider`, `PluginManager`, `PluginManagerFactory`, `ResourceStrategyFactory`,
`WorkflowFactory`) and the exceptions are final with nothing to replace them: register
your own factory instead of extending one, and catch `Exception\ExceptionInterface` or
the SPL parents instead of subclassing the exceptions.

| 1.x: extend | 2.0: extend instead | 2.0 final class |
| --- | --- | --- |
| `Workflow\PageWorkflow` | `Workflow\AbstractPageWorkflow` | `Workflow\PageWorkflow` |
| `Workflow\PageActionWorkflow` | `Workflow\AbstractPageActionWorkflow` | `Workflow\PageActionWorkflow` |
| `Workflow\ArticleWorkflow` | `Workflow\AbstractArticleWorkflow` | `Workflow\ArticleWorkflow` |
| `Strategy\ResourceStrategy` | `Strategy\AbstractResourceStrategy` | `Strategy\ResourceStrategy` |
| `Navigation\WorkflowNavigationFactory` | `Navigation\AbstractWorkflowNavigationFactory` | `Navigation\WorkflowNavigationFactory` |

```php
// 1.x
class NewsWorkflow extends \Contenir\Mvc\Workflow\Workflow\ArticleWorkflow { /* … */ }
class HomeWorkflow extends \Contenir\Mvc\Workflow\Workflow\PageWorkflow { /* … */ }
class SpaWorkflow extends \Contenir\Mvc\Workflow\Workflow\PageActionWorkflow { /* … */ }
class ResourceStrategy extends \Contenir\Mvc\Workflow\Strategy\ResourceStrategy { /* … */ }
class CmsNavigationFactory extends \Contenir\Mvc\Workflow\Navigation\WorkflowNavigationFactory { /* … */ }

// 2.0
class NewsWorkflow extends \Contenir\Mvc\Workflow\Workflow\AbstractArticleWorkflow { /* … */ }
class HomeWorkflow extends \Contenir\Mvc\Workflow\Workflow\AbstractPageWorkflow { /* … */ }
class SpaWorkflow extends \Contenir\Mvc\Workflow\Workflow\AbstractPageActionWorkflow { /* … */ }
class ResourceStrategy extends \Contenir\Mvc\Workflow\Strategy\AbstractResourceStrategy { /* … */ }
class CmsNavigationFactory extends \Contenir\Mvc\Workflow\Navigation\AbstractWorkflowNavigationFactory { /* … */ }
```

The bodies don't change. Notes:

- `AbstractArticleWorkflow::getRouteConfig()` is no longer abstract: it is the article
  route `ArticleWorkflow` had. Subclasses that implemented their own keep overriding it.
- `PageActionWorkflow` no longer extends `PageWorkflow`; both extend
  `AbstractPageWorkflow`. Replace `instanceof PageWorkflow` checks with
  `instanceof AbstractPageWorkflow`.
- `ResourceStrategyFactory` builds the requested `AbstractResourceStrategy` subclass, or
  `ResourceStrategy` when the interface or an abstract class is requested. Registering
  `App\ResourceStrategy::class => ResourceStrategyFactory::class` works as before.
- The strategy's plugin manager is typed against
  `Laminas\ServiceManager\PluginManagerInterface` (constructor, `getPluginManager()`,
  `setPluginManager()`), not the now-final `PluginManager`.

```php
// 1.x
public function __construct(PluginManager $pluginManager, ResourceAdapterInterface $repository, iterable $options = [])

// 2.0
public function __construct(PluginManagerInterface $pluginManager, ResourceAdapterInterface $repository, iterable $options = [])
```

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

## Package renamed in 2.1

From 2.1, the package is published as
`contenir/contenir-workflow-laminas-mvc`. It declares `replace` for
`contenir/contenir-mvc-workflow`, so the two can never be installed together.
Switch the requirement:

```bash
composer remove contenir/contenir-mvc-workflow && composer require contenir/contenir-workflow-laminas-mvc:^2.1
```

No code changes are needed: namespaces and classes are unchanged.
