<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Metadata\MetadataInterface;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use Contenir\Mvc\Workflow\Resource\ResourceProperty;
use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;
use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use Laminas\Cache\Exception\ExceptionInterface as CacheException;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;

use function array_key_exists;
use function count;
use function get_debug_type;
use function is_array;
use function is_scalar;
use function method_exists;
use function sprintf;
use function str_replace;
use function ucwords;

/**
 * Base strategy, and the extension point for applications: walks the
 * resource tree once, asking each resource's workflow for its
 * route and navigation page, and caches the result when a cache is set.
 *
 * Options: "cache" (a laminas-cache StorageInterface), "cache_key" (default
 * "ResourceStrategyCache") and "use_parent_as_landing_page" (default false).
 *
 * @api
 *
 * @mago-expect lint:cyclomatic-complexity 1.x public API kept intact; splitting the class is a suggested follow-up.
 * @mago-expect lint:kan-defect 1.x public API kept intact; splitting the class is a suggested follow-up.
 * @mago-expect lint:too-many-methods 1.x public API kept intact; splitting the class is a suggested follow-up.
 */
abstract class AbstractResourceStrategy implements ResourceStrategyInterface
{
    private PluginManagerInterface $pluginManager;
    private ResourceAdapterInterface $repository;
    private ?StorageInterface $cache = null;
    private bool              $built = false;

    /** @var array<array-key, mixed> */
    protected array $options = [
        'use_parent_as_landing_page' => false,
        'cache_key'                  => 'ResourceStrategyCache',
    ];

    /** @var array<array-key, mixed> */
    protected array $resources = [];

    /**
     * @param iterable<array-key, mixed> $options
     *
     * @throws InvalidArgumentException When an option is unknown.
     */
    public function __construct(
        PluginManagerInterface $pluginManager,
        ResourceAdapterInterface $resourceRepository,
        iterable $options = [],
    ) {
        $this->pluginManager = $pluginManager;
        $this->repository    = $resourceRepository;

        $this->setOptions($options);
    }

    /**
     * @param array<string, mixed> $page
     *
     * @return array<array-key, mixed>
     *
     * @mago-expect analysis:mixed-assignment Page specifications are untyped arrays; narrowed here.
     */
    private static function pagesOf(array $page): array
    {
        $pages = $page['pages'] ?? [];

        return is_array($pages) ? $pages : [];
    }

    /**
     * The cache, or null when the strategy rebuilds once per instance.
     */
    public function getCache(): ?StorageInterface
    {
        return $this->cache;
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws CacheException
     * @throws InvalidArgumentException When a workflow or child resource has the wrong type.
     * @throws RuntimeException When a workflow cannot build its route.
     * @throws ContainerExceptionInterface
     */
    #[Override]
    public function getNavigationConfig(): array
    {
        $this->build();

        return $this->section('navigation');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException When the workflow does not extend AbstractWorkflow.
     * @throws RuntimeException When the workflow has no resource.
     *
     * @mago-expect analysis:mixed-assignment Route pages are untyped workflow configuration; narrowed here.
     */
    #[Override]
    public function getNavigationPage(WorkflowInterface $workflow): array
    {
        if (! $workflow instanceof AbstractWorkflow) {
            throw new InvalidArgumentException(sprintf(
                'Workflows must extend %s, %s given',
                AbstractWorkflow::class,
                $workflow::class,
            ));
        }

        $resource = $workflow->getResource() ?? throw new RuntimeException(sprintf(
            'Workflow %s has no resource set',
            $workflow::class,
        ));
        $routeId    = $workflow->getRouteId();
        $routeTitle = $workflow->getRouteTitle();
        $routePages = $workflow->getRoutePages();
        $lastmod    = $resource instanceof MetadataInterface ? $resource->getMetaModified() : null;

        $page = [
            'label'      => ResourceProperty::string($resource, 'title_short')
            ?? ResourceProperty::string($resource, 'title'),
            'route'      => $routeId,
            'lastmod'    => $lastmod?->format('Y-m-d H:i:s'),
            'changefreq' => $workflow->getPageChangeFrequency(),
            'priority'   => $workflow->getPriority(),
            'visible'    => ResourceProperty::bool($resource, 'visible'),
            'resource'   => $workflow->getResourceId(),
            'pages'      => [],
        ];

        $hasLandingPage = count($routePages) > 1
            ? $workflow->getLandingPage()
            : count(ResourceProperty::children($resource)) > 0 && $this->useParentAsLandingPage();

        $subPages = [];
        foreach ($routePages as $subRouteId => $routeSubPages) {
            $subRoutePages = is_array($routeSubPages) ? $routeSubPages : [];
            foreach ($this->getNavigationSubPage(
                $resource,
                $routeId,
                (string) $subRouteId,
                $subRoutePages,
            ) as $subPage) {
                $subPages[] = $subPage;
            }
        }

        if ($hasLandingPage) {
            $landingPage                  = $page;
            $landingPage['label']         = $routeTitle ?? ResourceProperty::string($resource, 'title');
            $landingPage['useRouteMatch'] = [] === $routePages;
            if ([] !== $routePages) {
                $landingPage['pages'] = $subPages;
            }

            $page['pages'] = [$landingPage];

            return $page;
        }

        if ([] !== $routePages) {
            $page['useRouteMatch'] = false;
            $page['pages']         = $subPages;
        }

        return $page;
    }

    public function getPluginManager(): PluginManagerInterface
    {
        return $this->pluginManager;
    }

    public function getRepository(): ResourceAdapterInterface
    {
        return $this->repository;
    }

    /**
     * @return array<array-key, mixed>
     *
     * @throws CacheException
     * @throws InvalidArgumentException When a workflow or child resource has the wrong type.
     * @throws RuntimeException When a workflow cannot build its route.
     * @throws ContainerExceptionInterface
     */
    #[Override]
    public function getRouteConfig(): array
    {
        $this->build();

        return $this->section('route');
    }

    public function setCache(StorageInterface $cacheContainer): void
    {
        $this->cache = $cacheContainer;
    }

    /**
     * @param array<array-key, mixed> $navigation
     */
    public function setNavigationConfig(array $navigation = []): void
    {
        $this->resources['navigation'] = $navigation;
    }

    /**
     * Calls the matching setter ("cache" calls setCache()) or stores a known option.
     *
     * @param iterable<array-key, mixed> $options
     *
     * @throws InvalidArgumentException When an option is unknown.
     *
     * @mago-expect analysis:mixed-assignment Options are untyped input; setters type them.
     * @mago-expect analysis:string-member-selector Options map to setters by name, as in 1.x.
     */
    public function setOptions(iterable $options): self
    {
        foreach ($options as $key => $value) {
            $method = 'set'
            . str_replace(
                search: ' ',
                replace: '',
                subject: ucwords(str_replace(
                    search: '_',
                    replace: ' ',
                    subject: (string) $key,
                )),
            );
            if (method_exists($this, $method)) {
                $this->{$method}($value);

                continue;
            }

            if (! array_key_exists($key, $this->options)) {
                throw new InvalidArgumentException(sprintf('Method %s() does not exist', $method));
            }

            $this->options[$key] = $value;
        }

        return $this;
    }

    public function setPluginManager(PluginManagerInterface $pluginManager): void
    {
        $this->pluginManager = $pluginManager;
    }

    public function setRepository(ResourceAdapterInterface $repository): void
    {
        $this->repository = $repository;
    }

    /**
     * @param array<array-key, mixed> $route
     */
    public function setRouteConfig(array $route = []): void
    {
        $this->resources['route'] = $route;
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException When a workflow or child resource has the wrong type.
     * @throws RuntimeException When a workflow cannot build its route.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Cache items are untyped; anything but an array is rebuilt.
     */
    protected function build(): void
    {
        $cache = $this->cache;
        if (null === $cache) {
            if (! $this->built) {
                $this->collect();
                $this->built = true;
            }

            return;
        }

        $cacheKey = $this->cacheKey();
        if ($cache->hasItem($cacheKey)) {
            $cached = $cache->getItem($cacheKey);
            if (is_array($cached)) {
                $this->resources = $cached;

                return;
            }
        }

        $this->collect();
        $cache->setItem($cacheKey, $this->resources);
    }

    /**
     * @param array<array-key, mixed> $routePages
     *
     * @return list<array<string, mixed>>
     *
     * @mago-expect analysis:mixed-assignment Route pages are untyped workflow configuration; narrowed here.
     */
    protected function getNavigationSubPage(
        ResourceInterface $resource,
        string $parentRouteId,
        string $routeId,
        array $routePages,
    ): array {
        $pages      = [];
        $subRouteId = "{$parentRouteId}/{$routeId}";

        foreach ($routePages as $routePage) {
            if (! is_array($routePage)) {
                continue;
            }

            $landingPageTitle = $routePage['landingTitle'] ?? false;
            $subPage          = [
                'label'   => $routePage['title']
                ?? ResourceProperty::string($resource, 'title_short')
                ?? ResourceProperty::string($resource, 'title'),
                'visible' => ResourceProperty::bool($resource, 'visible'),
                'route'   => $subRouteId,
                'params'  => $routePage['params'] ?? [],
                'pages'   => [],
            ];

            if ($landingPageTitle) {
                $landingSubPage          = $subPage;
                $landingSubPage['label'] = $landingPageTitle;
                $subPage['pages']        = [$landingSubPage];
            }

            $childPages = $routePage['pages'] ?? null;
            if (is_array($childPages)) {
                foreach ($childPages as $subPageRouteId => $routeSubPages) {
                    $nestedRoutePages = is_array($routeSubPages) ? $routeSubPages : [];
                    $nestedPages      = $this->getNavigationSubPage(
                        $resource,
                        $subRouteId,
                        (string) $subPageRouteId,
                        $nestedRoutePages,
                    );
                    foreach ($nestedPages as $nestedPage) {
                        $subPage['pages'][] = $nestedPage;
                    }
                }
            }

            $pages[] = $subPage;
        }

        return $pages;
    }

    /**
     * @throws InvalidArgumentException When the plugin is not an AbstractWorkflow.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment PluginManagerInterface::build() is untyped; narrowed here.
     */
    protected function getResourceWorkFlow(ResourceInterface $resource): WorkflowInterface
    {
        $workflow = $this->pluginManager->build(ResourceProperty::string($resource, 'workflow') ?? 'page');
        if (! $workflow instanceof AbstractWorkflow) {
            throw new InvalidArgumentException(sprintf(
                'Workflows must extend %s, %s given',
                AbstractWorkflow::class,
                get_debug_type($workflow),
            ));
        }

        $workflow->setResource($resource);

        return $workflow;
    }

    /**
     * Records each resource's route and returns the navigation pages of the
     * visible ones, recursing into their children.
     *
     * @param iterable<ResourceInterface> $resources
     * @param array<array-key, mixed> $pages
     *
     * @return array<array-key, mixed>
     *
     * @throws InvalidArgumentException When a workflow or child resource has the wrong type.
     * @throws RuntimeException When a workflow cannot build its route.
     * @throws ContainerExceptionInterface
     */
    protected function process(iterable $resources, array $pages = []): array
    {
        foreach ($resources as $resource) {
            $workflow = $this->getResourceWorkFlow($resource);
            $config   = $workflow->getRouteConfig();

            if ([] !== $config) {
                $routes                          = $this->section('route');
                $routes[$workflow->getRouteId()] = $config;
                $this->resources['route']        = $routes;
            }

            if (! ResourceProperty::bool($resource, 'visible')) {
                continue;
            }

            $page     = $this->getNavigationPage($workflow);
            $children = ResourceProperty::children($resource);

            if ([] !== $children) {
                $page['pages'] = $this->process($children, self::pagesOf($page));
            }

            $pages[] = $page;
        }

        return $pages;
    }

    /**
     * @mago-expect analysis:mixed-assignment Options are untyped; narrowed here.
     */
    private function cacheKey(): string
    {
        $key = $this->options['cache_key'] ?? null;

        return is_scalar($key) ? (string) $key : 'ResourceStrategyCache';
    }

    /**
     * @throws InvalidArgumentException When a workflow or child resource has the wrong type.
     * @throws RuntimeException When a workflow cannot build its route.
     * @throws ContainerExceptionInterface
     */
    private function collect(): void
    {
        $this->resources = [
            'route'      => [],
            'navigation' => [],
        ];

        $this->resources['navigation'] = $this->process($this->getRepository()->getWorkflowResources());
    }

    /**
     * @return array<array-key, mixed>
     *
     * @mago-expect analysis:mixed-assignment Resources may come from the cache; narrowed here.
     */
    private function section(string $name): array
    {
        $section = $this->resources[$name] ?? [];

        return is_array($section) ? $section : [];
    }

    /**
     * @mago-expect analysis:mixed-operand The option is read for truthiness, as in 1.x.
     */
    private function useParentAsLandingPage(): bool
    {
        return (bool) ($this->options['use_parent_as_landing_page'] ?? false);
    }
}
