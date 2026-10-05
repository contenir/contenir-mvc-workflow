# Configuration

The module (`Module::getConfig()`) and `ConfigProvider` register two services and
default to no strategy:

| Service | Factory | Notes |
| --- | --- | --- |
| `Contenir\Mvc\Workflow\PluginManager` (alias `workflow_plugin_manager`) | `PluginManagerFactory` | Workflow plugins, configured by `workflow_manager` |
| `Strategy\ResourceStrategyInterface` (alias `workflow_strategy`) | `Strategy\ResourceStrategyFactory` | Builds a `ResourceStrategy` |

Applications usually register their own `ResourceStrategy` subclass with
`ResourceStrategyFactory` and name it in `workflow_manager.strategy.type`.

## `workflow_manager`

```php
'workflow_manager' => [
    // laminas-servicemanager configuration for the workflow plugin manager.
    // Keys are the values of the resources' "workflow" property.
    'aliases'    => ['page' => App\Workflow\PageWorkflow::class],
    'factories'  => [App\Workflow\PageWorkflow::class => Contenir\Mvc\Workflow\Workflow\WorkflowFactory::class],

    'strategy'   => [
        // Required: service name of the strategy.
        'type'       => App\Workflow\ResourceStrategy::class,

        // Required: service implementing Resource\ResourceAdapterInterface.
        'repository' => App\Model\ResourceRepository::class,

        // Optional: passed to the strategy's constructor.
        'options'    => [
            // laminas-cache storage service name, or a StorageInterface instance.
            // Omitted, null or '': the tree is built once per request.
            'cache'                      => 'DataCache',

            // Cache item key. Default "ResourceStrategyCache".
            'cache_key'                  => 'ResourceStrategyCache',

            // Give a parent with children a landing page of its own. Default false.
            'use_parent_as_landing_page' => false,
        ],
    ],

    // Required by WorkflowNavigationFactory: the navigation container's name.
    'navigation' => ['name' => 'cms'],
],
```

Any other `options` key is passed to the matching `set<Key>()` method of the
strategy, and an unknown key throws `Exception\InvalidArgumentException`.

## `workflow`

Titles and descriptions per workflow name. `WorkflowFactory` passes this to every
workflow it creates, and `AbstractWorkflow::getNavigationConfig()` uses the title as the
page label.

```php
'workflow' => [
    'news' => ['title' => 'News', 'description' => 'Articles and announcements'],
],
```

## Errors

Misconfiguration fails with `Contenir\Mvc\Workflow\Exception\InvalidArgumentException`.
Both exception classes implement `Exception\ExceptionInterface`.

| Where | Message |
| --- | --- |
| `Module::onBootstrap()` | `No workflow strategy configuration found` / `No workflow strategy type configured` |
| `Module::onBootstrap()` | `Workflow strategy "…" must implement …ResourceStrategyInterface` / `The "router" service must implement …RouteStackInterface` |
| `ResourceStrategyFactory` | `No repository found in workflow strategy configuration` / `Service "…" must be an instance of …` |
| `WorkflowNavigationFactory` | `No workflow manager configuration found` / `No workflow strategy configuration found` / `No workflow navigation configuration found` |
| `WorkflowFactory` | `WorkflowFactory can only create …AbstractWorkflow subclasses` |

A workflow used without a resource or a controller throws `Exception\RuntimeException`.
