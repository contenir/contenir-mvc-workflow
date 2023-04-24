<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Laminas\Router\Http\Literal;
use Traversable;

abstract class AbstractWorkflow implements WorkflowInterface
{
    protected $workflowConfig      = [];
    protected $workflowTitle       = 'Untitled';
    protected $workflowId          = null;
    protected $workflowDescription = null;
    protected $controller          = null;
    protected $routeId             = null;
    protected $routePath           = null;
    protected $routeTitle          = null;
    protected $segment             = null;
    protected $resourceId          = null;
    protected $pages               = [];
    protected $landingPage         = false;
    protected $changeFrequency     = null;
    protected $priority            = '0.5';

    public function __construct($workflowConfig = null)
    {
        if ($workflowConfig !== null) {
            $this->setConfig($workflowConfig);
        }
    }

    public function setResource($resource)
    {
        $this->resource = $resource;

        $this->resourceId = $resource->getResourceId();
        $this->workflowId = $resource->workflow ?? 'page';
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setConfig($config)
    {
        if (! is_array($config) && ! $config instanceof Traversable) {
            throw new InvalidArgumentException(sprintf(
                'Parameter provided to %s must be an array or Traversable',
                __METHOD__
            ));
        }

        $this->workflowConfig      = $config[$this->resourceId]           ?? [];
        $this->workflowTitle       = $this->workflowConfig['title']       ?? $this->workflowTitle;
        $this->workflowDescription = $this->workflowConfig['description'] ?? $this->workflowDescription;
    }

    /**
     * getRouteId
     *
     * @param  mixed $path
     *
     * @return String
     */
    public function getWorkflowId(): string
    {
        return $this->workflowId;
    }

    /**
     * getRouteId
     *
     * @param  mixed $path
     *
     * @return String
     */
    public function getRouteId(string $path = ''): string
    {
        if ($this->routeId === null) {
            $parts = [sprintf(
                '%s-%d',
                $this->getResource()->resource_type_id ?? null,
                $this->getResource()->resource_id ?? null
            ), $path];

            return sprintf('%s', join('/', array_filter($parts)));
        }

        return $this->routeId;
    }

    /**
     * getRoutePath
     *
     * @return String
     */
    public function getRoutePath(): string
    {
        if ($this->routePath === null) {
            $parts = explode('/', $this->resourceId);
            return sprintf('/%s', join('/', array_filter($parts)));
        }

        return $this->routePath;
    }

    /**
     * getRouteTitle
     *
     * @return void
     */
    public function getRouteTitle()
    {
        return $this->routeTitle;
    }

    /**
     * getRouteController
     *
     * @return String
     */
    public function getRouteController(): string
    {
        return "{$this->controller}";
    }

    /**
     * getRouteController
     *
     * @return String
     */
    public function getResourceId(): string
    {
        $controllerName = $this->controller;
        $controllerName = str_replace('\\Controller\\', '.', $controllerName);
        $controllerName = str_replace('Controller', '', $controllerName);
        $controllerName = strtolower($controllerName);

        $resource = sprintf('controller:%s', $controllerName);

        return "{$resource}";
    }

    /**
     * getRouteConfig
     *
     * @return Array
     */
    public function getRouteConfig(): array
    {
        $routeConfig = [
            'type'    => Literal::class,
            'options' => [
                'route'    => $this->getRoutePath(),
                'defaults' => [
                    'controller' => $this->getRouteController(),
                    'action'     => 'index'
                ]
            ],
            'may_terminate' => true,
            'child_routes'  => [

            ]
        ];

        return $routeConfig;
    }

    /**
     * getRoutePages
     *
     * @return Array
     */
    public function getRoutePages(): array
    {
        return $this->pages;
    }

    public function getNavigationConfig(): array
    {
        return [
            'label'      => $this->workflowTitle,
            'route'      => $this->getRouteId(),
            'changefreq' => $this->getPageChangeFrequency(),
            'priority'   => $this->getPriority(),
            'visible'    => true,
            'pages'      => []
        ];
    }

    /**
     * setResource
     *
     * @param  mixed $resource
     *
     * @return void
     */
    public function setLandingPage($flag)
    {
        $this->landingPage = (bool) $flag;
    }

    /**
     * getResource
     *
     * @return void
     */
    public function getLandingPage()
    {
        return $this->landingPage;
    }

    /**
     * getPageChangeFrequency
     *
     * @return String
     */
    public function getPageChangeFrequency(): ?string
    {
        return $this->changeFrequency;
    }

    /**
     * getPageChangeFrequency
     *
     * @return String
     */
    public function getPriority(): ?string
    {
        return $this->priority;
    }
}
