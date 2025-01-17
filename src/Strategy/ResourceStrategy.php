<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Metadata\MetadataInterface;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use DateTimeInterface;
use InvalidArgumentException;
use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\StorageInterface;
use Psr\Container\ContainerExceptionInterface;

use function array_key_exists;
use function count;
use function method_exists;
use function sprintf;
use function str_replace;
use function ucwords;

class ResourceStrategy implements ResourceStrategyInterface
{
    private PluginManager $pluginManager;
    private ResourceAdapterInterface $repository;
    private StorageInterface $cache;

    protected array $options = [
        'use_parent_as_landing_page' => false,
        'cache_key'                  => 'ResourceStrategyCache',
    ];

    protected array $resources = [];

    public function __construct(
        PluginManager $pluginManager,
        ResourceAdapterInterface $resourceRepository,
        iterable $options = []
    ) {
        $this->setPluginManager($pluginManager);
        $this->setRepository($resourceRepository);

        $this->setOptions($options);
    }

    public function setOptions(iterable $options): self
    {
        foreach ($options as $key => $value) {
            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            } elseif (array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                throw new InvalidArgumentException(sprintf(
                    'Method %s() does not exist',
                    $method
                ));
            }
        }

        return $this;
    }

    public function setPluginManager(PluginManager $pluginManager): void
    {
        $this->pluginManager = $pluginManager;
    }

    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    public function setRepository(ResourceAdapterInterface $repository): void
    {
        $this->repository = $repository;
    }

    public function getRepository(): ResourceAdapterInterface
    {
        return $this->repository;
    }

    public function setCache(StorageInterface $cacheContainer): void
    {
        $this->cache = $cacheContainer;
    }

    public function getCache(): StorageInterface
    {
        return $this->cache;
    }

    public function setNavigationConfig(array $navigation = []): void
    {
        $this->resources['navigation'] = $navigation;
    }

    /**
     * @throws ExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getNavigationConfig(): array
    {
        $this->build();

        return $this->resources['navigation'];
    }

    public function setRouteConfig(array $route = []): void
    {
        $this->resources['route'] = $route;
    }

    /**
     * @throws ExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function getRouteConfig(): array
    {
        $this->build();

        return $this->resources['route'];
    }

    /**
     * @throws ExceptionInterface
     * @throws ContainerExceptionInterface
     */
    protected function build(): void
    {
        $cacheKey = $this->options['cache_key'];
        if (! $this->getCache()->hasItem($cacheKey)) {
            $this->resources = [
                'route'      => [],
                'navigation' => [],
            ];

            $this->resources['navigation'] = $this->process(
                $this->getRepository()->getWorkflowResources()
            );

            $this->getCache()->setItem($cacheKey, $this->resources);
        } else {
            $this->resources = $this->getCache()->getItem($cacheKey);
        }
    }

    /**
     * @throws ContainerExceptionInterface
     */
    protected function process(iterable $resources, array $pages = []): array
    {
        foreach ($resources as $resource) {
            $workflow = $this->getResourceWorkFlow($resource);
            $config   = $workflow->getRouteConfig();
            $routeId  = $workflow->getRouteId();

            if ($config) {
                $this->resources['route'][$routeId] = $config;
            }

            $page     = $this->getNavigationPage($workflow);
            $children = $resource->children;

            if (count($children)) {
                $page['pages'] = $this->process($children, $page['pages']);
            }

            $pages[] = $page;
        }

        return $pages;
    }

    public function getNavigationPage(WorkflowInterface $workflow): array
    {
        $resource       = $workflow->getResource();
        $hasLandingPage = false;
        $routeTitle     = $workflow->getRouteTitle();
        $routePages     = $workflow->getRoutePages();
        $lastmod        = $resource instanceof MetadataInterface ? $resource->getMetaModified() : null;

        $page = [
            'label'      => $resource->title_short ?? $resource->title,
            'route'      => $workflow->getRouteId(),
            'lastmod'    => $lastmod instanceof DateTimeInterface ? $lastmod->format('Y-m-d H:i:s') : null,
            'changefreq' => $workflow->getPageChangeFrequency(),
            'priority'   => $workflow->getPriority(),
            'visible'    => (bool) $resource->visible,
            'resource'   => $workflow->getResourceId(),
            'pages'      => [],
        ];

        if (count($routePages) > 1) {
            if ($workflow->getLandingPage()) {
                $hasLandingPage = true;
            }
        } else {
            $numberOfChildren       = count($resource->children);
            $useParentAsLandingPage = $this->options['use_parent_as_landing_page'];
            $hasLandingPage         = $numberOfChildren > 0 && $useParentAsLandingPage;
        }

        if ($hasLandingPage) {
            $landingPage                  = $page;
            $landingPage['label']         = $routeTitle ?? $resource->title;
            $landingPage['useRouteMatch'] = true;
            if (count($routePages)) {
                $landingPage['useRouteMatch'] = false;
                foreach ($routePages as $subRouteId => $routeSubPages) {
                    foreach (
                        $this->getNavigationSubPage(
                            $resource,
                            $workflow->getRouteId(),
                            $subRouteId,
                            $routeSubPages
                        ) as $subPage
                    ) {
                        $landingPage['pages'][] = $subPage;
                    }
                }
            }
            $page['pages'][] = $landingPage;
        } else {
            if (count($routePages)) {
                $page['useRouteMatch'] = false;
                foreach ($routePages as $subRouteId => $routeSubPages) {
                    foreach (
                        $this->getNavigationSubPage(
                            $resource,
                            $workflow->getRouteId(),
                            $subRouteId,
                            $routeSubPages
                        ) as $subPage
                    ) {
                        $page['pages'][] = $subPage;
                    }
                }
            }
        }

        return $page;
    }

    protected function getNavigationSubPage(
        object $resource,
        string $parentRouteId,
        string $routeId,
        array $routePages
    ): array {
        $pages = [];

        foreach ($routePages as $routePage) {
            $landingSubPage   = null;
            $subRouteId       = $parentRouteId . '/' . $routeId;
            $params           = $routePage['params'] ?? [];
            $landingPageTitle = $routePage['landingTitle'] ?? false;
            $subPage          = [
                'label'   => $routePage['title'] ?? $resource->title_short ?? $resource->title,
                'visible' => $resource->visible,
                'route'   => $subRouteId,
                'params'  => $params,
                'pages'   => [],
            ];

            if ($landingPageTitle) {
                $landingSubPage          = $subPage;
                $landingSubPage['label'] = $landingPageTitle;
                $subPage['pages'][]      = $landingSubPage;
            }

            if (isset($routePage['pages'])) {
                foreach ($routePage['pages'] as $subPageRouteId => $routeSubpages) {
                    foreach (
                        $this->getNavigationSubPage(
                            $resource,
                            $subRouteId,
                            $subPageRouteId,
                            $routeSubpages
                        ) as $routeSubPage
                    ) {
                        $subPage['pages'][] = $routeSubPage;
                    }
                }
            }

            $pages[] = $subPage;
        }

        return $pages;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    protected function getResourceWorkFlow(ResourceInterface $resource): WorkflowInterface
    {
        $workflow = $this->pluginManager->build($resource->workflow ?? 'page');
        $workflow->setResource($resource);

        return $workflow;
    }
}
