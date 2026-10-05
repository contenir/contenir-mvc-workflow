# Strategy

`Strategy\ResourceStrategy` asks the adapter for the root resources once, then walks
the tree. For each resource it:

1. builds the workflow named by the resource's `workflow` property;
2. records the workflow's route config under its route name, unless it is empty;
3. if the resource is `visible`, adds its navigation page, with its children's pages
   nested under it.

The result is cached under `cache_key` when a cache is configured. Without one, it is
built once per strategy instance (once per request). Clear the cache item when the
tree changes.

```php
$strategy->getRouteConfig();      // ['1-12' => [...route spec...], ...]
$strategy->getNavigationConfig(); // [['label' => 'About', 'route' => '1-12', 'pages' => [...]], ...]
```

## Navigation pages

`getNavigationPage(WorkflowInterface $workflow)` builds one page:

```php
[
    'label'      => 'About',                  // title_short, else title
    'route'      => '1-12',
    'lastmod'    => '2024-01-02 03:04:05',    // MetadataInterface::getMetaModified(), else null
    'changefreq' => 'weekly',
    'priority'   => '0.5',
    'visible'    => true,
    'resource'   => 'controller:application.about',
    'pages'      => [],
]
```

Child route pages (the workflow's `$pages`) are added under the page, with
`useRouteMatch` set to `false`. A **landing page** (a copy of the page, labelled with
the workflow's `$routeTitle`) is inserted between them when:

- the workflow has more than one child route and `$landingPage` is set, or
- the workflow has at most one child route, the resource has children, and the
  `use_parent_as_landing_page` option is on.

Applications commonly subclass the strategy to change single pages:

```php
final class ResourceStrategy extends \Contenir\Mvc\Workflow\Strategy\ResourceStrategy
{
    public function getNavigationPage(WorkflowInterface $workflow): array
    {
        if ($workflow->getRouteId() === 'redirect') {
            return ['label' => '…', 'uri' => '…', 'target' => '_blank'];
        }

        return parent::getNavigationPage($workflow);
    }
}
```

Children are still nested under an overridden page.

## Public API

| Method | Notes |
| --- | --- |
| `__construct(PluginManager, ResourceAdapterInterface, iterable $options = [])` | See [configuration](configuration.md) for the options |
| `setOptions(iterable)` | Calls `set<Key>()` setters or stores known options |
| `getPluginManager()` / `setPluginManager()` | |
| `getRepository()` / `setRepository()` | |
| `getCache(): ?StorageInterface` / `setCache()` | `null` when no cache is configured |
| `getRouteConfig()` / `setRouteConfig()` | Built routes; a cache hit or rebuild replaces what was set |
| `getNavigationConfig()` / `setNavigationConfig()` | Built navigation; same caveat |
| `getNavigationPage(WorkflowInterface)` | One page; the workflow must extend `AbstractWorkflow` and have a resource |

Protected extension points: `build()`, `process()`, `getNavigationSubPage()` and
`getResourceWorkFlow()`.
