# Navigation

`Navigation\WorkflowNavigationFactory` is a laminas-navigation factory that builds its
container from the strategy's navigation configuration instead of the `navigation`
config key.

```php
'service_manager'  => [
    'factories' => [
        'navigation.cms' => Contenir\Mvc\Workflow\Navigation\WorkflowNavigationFactory::class,
    ],
],
'workflow_manager' => [
    'navigation' => ['name' => 'cms'],
],
```

The factory reads `workflow_manager.strategy.type` to find the strategy, and calls
`getNavigationConfig()`. The pages are MVC pages bound to the application's router,
route match and request, so they can only be built once the module has bootstrapped
and added the routes.

`getName()` returns `workflow_manager.navigation.name` after the factory has run, and
`cms` before that. `WorkflowNavigationFactory` is final; to change the default name,
extend `AbstractWorkflowNavigationFactory`:

```php
final class CmsNavigationFactory extends AbstractWorkflowNavigationFactory
{
    protected string $name = 'cms';
}
```

Use the container like any laminas-navigation container, for menus, breadcrumbs and
sitemaps:

```php
echo $this->navigation('navigation.cms')->menu();
echo $this->navigation('navigation.cms')->sitemap(); // uses lastmod, changefreq and priority
```
