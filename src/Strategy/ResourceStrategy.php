<?php

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Metadata\MetadataInterface;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Adapter\ResourceAdapterInterface;
use DateTimeInterface;
use InvalidArgumentException;
use Traversable;

class ResourceStrategy
{
    private PluginManager $pluginManager;
    private ResourceAdapterInterface $repository;
    private $cache;

    protected $options = [
        'use_parent_as_landing_page' => false,
        'cache_key'                  => 'ResourceStrategyCache'
    ];

    protected $resources = [];

    public function __construct(
        PluginManager $pluginManager,
        ResourceAdapterInterface $resourceRepository,
        $options = []
    ) {
        $this->setPluginManager($pluginManager);
        $this->setRepository($resourceRepository);

        $this->setOptions($options);
    }

    public function setOptions($options)
    {
        if (! is_array($options) && ! $options instanceof Traversable) {
            throw new InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

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

    public function setPluginManager(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
    }

    public function getPluginManager()
    {
        return $this->pluginManager;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setCache($cacheContainer)
    {
        $this->cache = $cacheContainer;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function setNavigationConfig(array $navigation = [])
    {
        $this->resources['navigation'] = $navigation;
    }

    public function getNavigationConfig(): array
    {
        $this->build();

        return $this->resources['navigation'];
    }

    public function setRouteConfig(array $route = [])
    {
        $this->resources['route'] = $route;
    }

    public function getRouteConfig(): array
    {
        $this->build();

        return $this->resources['route'];
    }

    protected function build()
    {
        if (! empty($this->resources['route'])) {
            return;
        }

        $success = false;
        if ($this->getCache()) {
            $this->resources = $this->getCache()->getItem($this->options['cache_key'], $success);
            if ($success) {
                return;
            }
        }

        $this->resources = [
            'route'      => [],
            'navigation' => []
        ];

        $this->resources['navigation'] = $this->process(
            $this->getRepository()->getWorkflowResources(),
            []
        );

        if ($this->getCache()) {
            $this->getCache()->setItem($this->options['cache_key'], $this->resources);
        }

        return;
    }

    protected function process($resources, $pages = [])
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

    protected function getNavigationPage($workflow)
    {
        $resource       = $workflow->getResource();
        $hasLandingPage = false;
        $routeTitle     = $workflow->getRouteTitle();
        $routePages     = $workflow->getRoutePages();
        $lastmod        = ($resource instanceof MetadataInterface) ? $resource->getMetaModified() : null;

        $page = [
            'label'      => $resource->title_short ?? $resource->title,
            'route'      => $workflow->getRouteId(),
            'lastmod'    => ($lastmod instanceof DateTimeInterface) ? $lastmod->format('Y-m-d H:i:s') : null,
            'changefreq' => $workflow->getPageChangeFrequency(),
            'priority'   => $workflow->getPriority(),
            'visible'    => (bool) $resource->visible,
            'resource'   => $workflow->getResourceId(),
            'pages'      => []
        ];

        if (count($routePages) > 1) {
            if ($workflow->getLandingPage()) {
                $hasLandingPage = true;
            }
        } else {
            $numberOfChildren       = count($resource->children);
            $useParentAsLandingPage = $this->options['use_parent_as_landing_page'];
            $hasLandingPage         = ($numberOfChildren > 0 && $useParentAsLandingPage);
        }

        if ($hasLandingPage) {
            $landingPage                  = $page;
            $landingPage['label']         = $routeTitle ?? $resource->title;
            $landingPage['useRouteMatch'] = true;
            if (count($routePages)) {
                $landingPage['useRouteMatch'] = false;
                foreach ($routePages as $subRouteId => $routeSubPages) {
                    foreach ($this->getNavigationSubPage($resource, $workflow->getRouteId(), $subRouteId, $routeSubPages) as $subPage) {
                        $landingPage['pages'][] = $subPage;
                    }
                }
            }
            $page['pages'][]              = $landingPage;
        } else {
            if (count($routePages)) {
                $page['useRouteMatch'] = false;
                foreach ($routePages as $subRouteId => $routeSubPages) {
                    foreach ($this->getNavigationSubPage($resource, $workflow->getRouteId(), $subRouteId, $routeSubPages) as $subPage) {
                        $page['pages'][] = $subPage;
                    }
                }
            }
        }

        return $page;
    }

    protected function getNavigationSubPage($resource, $parentRouteId, $routeId, $routePages)
    {
        $pages = [];

        foreach ($routePages as $routePage) {
            $landingSubPage   = null;
            $subRouteId       = $parentRouteId . '/' . $routeId;
            $params           = $routePage['params']       ?? [];
            $landingPageTitle = $routePage['landingTitle'] ?? false;
            $subPage          = [
                'label'   => $routePage['title'] ?? $resource->title_short ?? $resource->title,
                'visible' => $resource->visible,
                'route'   => $subRouteId,
                'params'  => $params,
                'pages'   => []
            ];

            if ($landingPageTitle) {
                $landingSubPage          = $subPage;
                $landingSubPage['label'] = $landingPageTitle;
                $subPage['pages'][]      = $landingSubPage;
            }

            if (isset($routePage['pages'])) {
                foreach ($routePage['pages'] as $subPageRouteId => $routeSubpages) {
                    foreach ($this->getNavigationSubPage($resource, $subRouteId, $subPageRouteId, $routeSubpages) as $routeSubPage) {
                        $subPage['pages'][] = $routeSubPage;
                    }
                }
            }

            $pages[] = $subPage;
        }

        return $pages;
    }

    protected function getResourceWorkFlow($resource)
    {
        $workflow = $this->pluginManager->build($resource->workflow ?? 'page');
        $workflow->setResource($resource);

        return $workflow;
    }
}
