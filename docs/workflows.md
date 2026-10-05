# Workflows

A workflow turns one resource into a route and a navigation page. Workflows are
plugins of `Contenir\Mvc\Workflow\PluginManager`, looked up by the resource's
`workflow` property (`page` when it has none), and must extend `AbstractWorkflow`.

## Built-in workflows

Each built-in workflow is a final class with an abstract base to extend:

| Final class | Extend | Route | Actions |
| --- | --- | --- | --- |
| `PageWorkflow` | `AbstractPageWorkflow` | `Literal` at the resource path | `index` |
| `PageActionWorkflow` | `AbstractPageActionWorkflow` | `segment` `"<path>[/:action]"` | `index` by default, any action by URL |
| `ArticleWorkflow` | `AbstractArticleWorkflow` | `Literal` at the resource path, plus a `segment` child route `"[/:slug]"` named after `$segment` (`post`) | `index` for the listing, `view` for an article |

Every route's defaults carry `controller`, `action` and `resource_id` (the resource's
primary keys).

## Writing a workflow

Extend an abstract base and declare its settings as protected properties:

```php
final class ContactWorkflow extends AbstractPageActionWorkflow
{
    protected ?string $controller = ContactController::class;
}
```

| Property | Type | Default | Meaning |
| --- | --- | --- | --- |
| `$controller` | `?string` | `null` | Controller class for the route. Required by the built-in route configs. |
| `$routeId` | `?string` | `null` | Fixed route name, instead of `"<resource_type_id>-<resource_id>"`. |
| `$routePath` | `?string` | `null` | Fixed path, instead of `"/" . slug`. |
| `$routeTitle` | `?string` | `null` | Label of the landing page, when there is one. |
| `$pages` | `array` | `[]` | Navigation pages for child routes (below). |
| `$landingPage` | `bool` | `false` | Add a landing page when `$pages` has more than one entry. |
| `$changeFrequency` | `?string` | `null` | Sitemap `changefreq`. `ArticleWorkflow`: `monthly`. |
| `$priority` | `string` | `'0.5'` | Sitemap `priority`. |
| `$segment` | `?string` | `null` | `ArticleWorkflow` child route name: `post`. |

For a custom route, extend `AbstractWorkflow` and implement `getRouteConfig()`. Return
`[]` for a resource that should appear in the navigation without a route of its own.

```php
final class RedirectWorkflow extends AbstractWorkflow
{
    protected ?string $controller = RedirectController::class;

    public function getRouteConfig(): array
    {
        return [
            'type'    => Segment::class,
            'options' => [
                'route'    => $this->getRoutePath() . '[/:target]',
                'defaults' => ['controller' => $this->getRouteController(), 'action' => 'redirect'],
            ],
        ];
    }
}
```

## Child route pages

`$pages` maps child route names to lists of navigation pages:

```php
protected array $pages = [
    'post' => [
        [
            'title'        => 'Article',           // label; defaults to the resource's title_short, then title
            'params'       => ['slug' => 'intro'], // route params
            'landingTitle' => 'All articles',      // also list the page under itself with this label
            'pages'        => ['comments' => [['title' => 'Comments']]], // nested child routes
        ],
    ],
];
```

Each page routes to `"<route id>/<child route name>"`, nested names joined with `/`.

## Public API

| Method | Returns |
| --- | --- |
| `setResource(ResourceInterface)` / `getResource()` | The resource. Setting it also resolves the workflow's `workflow` configuration. |
| `setConfig(iterable)` | Per-workflow config (the `workflow` key), keyed by workflow name |
| `getWorkflowId()` | The resource's workflow name; throws before a resource is set |
| `getRouteId(string $path = '')` | Route name, with `$path` appended |
| `getRoutePath()` | Route path |
| `getRouteTitle()` | `$routeTitle` |
| `getRouteController()` | `$controller`; throws when unset |
| `getResourceId()` | ACL resource name, e.g. `controller:application.index` |
| `getRouteConfig()` | laminas-router specification |
| `getRoutePages()` | `$pages` |
| `getNavigationConfig()` | A page for the workflow itself, labelled with the configured title |
| `setLandingPage(bool)` / `getLandingPage()` | `$landingPage` |
| `getPageChangeFrequency()`, `getPriority()` | Sitemap values |

`WorkflowFactory` creates any `AbstractWorkflow` subclass without constructor arguments,
and passes it the `workflow` configuration.
