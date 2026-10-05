# contenir/contenir-mvc-workflow

[![Continuous Integration](https://github.com/contenir/contenir-mvc-workflow/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-mvc-workflow/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-mvc-workflow/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-mvc-workflow)

Builds laminas-mvc routes and a laminas-navigation tree from the resource (page) tree of a
[Contenir CMS](https://github.com/contenir) site.

Each resource names a **workflow** (`page`, `news`, `contact`, ...). The workflow decides
the resource's route (a literal page, a page with actions, an article listing with a
`/:slug` child route) and its navigation page. A **strategy** walks the tree once,
caches the result, adds the routes to the router on bootstrap and feeds the navigation
factory.

- **Workflows:** extend `AbstractPageWorkflow`, `AbstractPageActionWorkflow`,
  `AbstractArticleWorkflow` or `AbstractWorkflow`. The concrete `PageWorkflow`,
  `PageActionWorkflow` and `ArticleWorkflow` are final. See [docs/workflows.md](docs/workflows.md).
- **Strategy:** `ResourceStrategy` (final), `AbstractResourceStrategy` to customise it, and the factory. See [docs/strategy.md](docs/strategy.md).
- **Navigation:** `WorkflowNavigationFactory`. See [docs/navigation.md](docs/navigation.md).
- **Configuration:** every key, with defaults. See [docs/configuration.md](docs/configuration.md).

## Requirements

- PHP 8.3, 8.4 or 8.5
- laminas-mvc 3.8+, laminas-router 3.13+, laminas-navigation 2.19+, laminas-servicemanager 3.22+,
  laminas-cache 3.12+
- `contenir/contenir-metadata` 2.x

The 1.x releases, which support PHP 8.1+, remain available from the `1.x` branch and
`v1.*` tags. See [UPGRADE-2.0.md](UPGRADE-2.0.md).

## Installation

```bash
composer require contenir/contenir-mvc-workflow
```

With the laminas component installer, the `Contenir\Mvc\Workflow` module registers itself.
Mezzio and config-aggregator applications can use `Contenir\Mvc\Workflow\ConfigProvider`
instead.

## Usage

### 1. Resources

The tree comes from a service implementing `Resource\ResourceAdapterInterface`. It
returns the top-level resources, each implementing `Resource\ResourceInterface`:

```php
interface ResourceInterface
{
    public function getSlug(): string;        // "about/team"

    public function getPrimaryKeys(): array;  // ['resource_id' => 12]
}
```

The workflows also read these properties when present. They are usually entity
columns, read through `__get()`:

| Property | Used for | Default |
| --- | --- | --- |
| `workflow` | Workflow plugin name | `page` |
| `resource_type_id`, `resource_id` | Route name `"<type>-<id>"` | |
| `title`, `title_short` | Navigation labels (short title first) | |
| `visible` | Whether the resource appears in the navigation (it is routed either way) | hidden |
| `children` | Iterable of child resources | none |

Resources that also implement `Contenir\Metadata\MetadataInterface` get a sitemap
`lastmod` from `getMetaModified()`.

### 2. Workflows

```php
use Application\Controller\NewsController;
use Contenir\Mvc\Workflow\Workflow\AbstractArticleWorkflow;

final class NewsWorkflow extends AbstractArticleWorkflow
{
    protected ?string $controller      = NewsController::class;
    protected ?string $changeFrequency = 'weekly';
    protected string $priority         = '1.0';
}
```

### 3. Configuration

```php
use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyFactory;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;

return [
    'service_manager'  => [
        'factories' => [ResourceStrategy::class => ResourceStrategyFactory::class],
    ],
    'workflow_manager' => [
        'aliases'    => ['page' => PageWorkflow::class, 'news' => NewsWorkflow::class],
        'factories'  => [
            PageWorkflow::class => WorkflowFactory::class,
            NewsWorkflow::class => WorkflowFactory::class,
        ],
        'strategy'   => [
            'type'       => ResourceStrategy::class,
            'repository' => ResourceRepository::class,
            'options'    => ['cache' => 'DataCache'],
        ],
        'navigation' => ['name' => 'cms'],
    ],
    'workflow'         => [
        'news' => ['title' => 'News'],
    ],
];
```

On bootstrap the module adds every resource's route to the router. `/news` routes to
`NewsController::indexAction()` and `/news/some-article` to `viewAction()`, each with a
`resource_id` route parameter holding the resource's primary keys.

### 4. Navigation

```php
'navigation'      => ['cms' => []],
'service_manager' => [
    'factories' => ['navigation.cms' => Contenir\Mvc\Workflow\Navigation\WorkflowNavigationFactory::class],
],
```

## Development

The QA toolchain is [php-db/phpdb-qa-tools](https://github.com/php-db/phpdb-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed
separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: workflows, strategy and factories against test doubles
composer test-integration  # integration suite: real ServiceManager, router and navigation
composer test-coverage     # both suites, clover.xml for Codecov
composer mutation-test     # Infection mutation testing over both suites (needs Xdebug or PCOV)
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
