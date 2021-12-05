<?php

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\PluginManager;

class ResourceStrategy
{
    private $repository;
    private $pluginManager;
    private $cacheContainer;
    private $cacheContainerKey = 'ResourceStrategyCache';

    protected $config = [
        'navigation' => [],
        'route' => [],
    ];

    public function __construct(
        PluginManager $pluginManager,
        $resourceRepository,
        $cacheContainer = null
    ) {
        $this->repository = $resourceRepository;
        $this->pluginManager = $pluginManager;

        $resources = $this->repository->find([
            'resource_type_id' => 'page',
            'parent_id IS NULL',
            'active' => 'active'
        ], [
            'sequence ASC'
        ]);

        if ($cacheContainer) {
            $this->config = $cacheContainer->getItem($this->cacheContainerKey, $success);
            if (! $success) {
                $this->config['navigation'] = $this->process($resources);
                $cacheContainer->setItem($this->cacheContainerKey, $this->config);
            }

        } else {
            $this->config['navigation'] = $this->process($resources);
        }
    }

    public function getNavigationConfig(): array
    {
        return $this->config['navigation'];
    }

    public function getRouteConfig(): array
    {
        return $this->config['route'];
    }

    protected function process($resources, $pages = [])
    {
        foreach ($resources as $resource) {
            $workflow = $this->getResourceWorkFlow($resource);
            $config = $workflow->getRouteConfig();
            $routeId = $workflow->getRouteId();

            if ($config) {
                $this->config['route'][$routeId] = $config;
            }

            $page = $this->getNavigationPage($workflow);
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
        $resource = $workflow->getResource();
        $hasLandingPage = false;
        $routeTitle = $workflow->getRouteTitle();
        $routePages = $workflow->getRoutePages();

        $page = [
            'label' => $resource->title_short ?? $resource->title,
            'route' => $workflow->getRouteId(),
            'lastmod' => ($resource instanceof MetaDataInterface) ? $resource->getMetaModified() : null,
            'changefreq' => $workflow->getPageChangeFrequency(),
            'priority' => $workflow->getPriority(),
            'visible' => (bool) $resource->visible,
            'resource' => $workflow->getResourceId(),
            'pages' => []
        ];

        if (count($routePages) > 1) {
            if ($workflow->getLandingPage()) {
                $hasLandingPage = true;
            }
        } elseif (count($resource->children)) {
            $hasLandingPage = true;
        }

        if ($hasLandingPage) {
            $landingPage = $page;
            $landingPage['label'] = $routeTitle ?? $resource->title;
            $landingPage['useRouteMatch'] = true;
            $page['pages'][] = $landingPage;
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

    protected function getNavigationSubPage($resource, $parentRouteId, $routeId, $routeSubpages)
    {
        $pages = [];

        foreach ($routeSubpages as $routeSubpage) {
            $landingSubPage = null;
            $subRouteId = $parentRouteId . '/' . $routeId;
            $params = $routeSubpage['params'] ?? [];
            $landingPageTitle = $routeSubpage['landingTitle'] ?? false;
            $subPage = [
                'label' => $routeSubpage['title'] ?? $resource->title_short ?? $resource->title,
                'visible' =>  $resource->visible,
                'route' => $subRouteId,
                'params' => $params,
                'pages' => []
            ];
            if ($landingPageTitle) {
                $landingSubPage = $subPage;
                $landingSubPage['label'] = $landingPageTitle;
                $subPage['pages'][] = $landingSubPage;
            }
            if (count($routeSubpage['pages'])) {
                die('x');
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