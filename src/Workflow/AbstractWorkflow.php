<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Adapter\ResourceAdapterInterface;

use function array_filter;
use function explode;
use function implode;
use function sprintf;
use function str_replace;
use function strtolower;

abstract class AbstractWorkflow implements WorkflowInterface
{
    private ?ResourceAdapterInterface $resource = null;
    protected array $workflowConfig             = [];
    protected ?string $workflowTitle            = null;
    protected ?string $workflowId               = null;
    protected ?string $workflowDescription      = null;
    protected ?string $controller               = null;
    protected ?string $routeId                  = null;
    protected ?string $routePath                = null;
    protected ?string $routeTitle               = null;
    protected ?string $segment                  = null;
    protected string|array|null $resourceId     = null;
    protected array $pages                      = [];
    protected bool $landingPage                 = false;
    protected ?string $changeFrequency          = null;
    protected string $priority                  = '0.5';

    public function __construct(iterable $workflowConfig = [])
    {
        if ($workflowConfig !== null) {
            $this->setConfig($workflowConfig);
        }
    }

    public function setResource(ResourceAdapterInterface $resource): void
    {
        $this->resource = $resource;

        $this->resourceId = $resource->getPrimaryKeys();
        $this->workflowId = $resource->workflow ?? 'page';
    }

    public function getResource(): ?ResourceAdapterInterface
    {
        return $this->resource;
    }

    public function setConfig(iterable $config): void
    {
        $this->workflowConfig      = $config[$this->resourceId] ?? [];
        $this->workflowTitle       = $this->workflowConfig['title'] ?? $this->workflowTitle;
        $this->workflowDescription = $this->workflowConfig['description'] ?? $this->workflowDescription;
    }

    /**
     * getRouteId
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
     * @param mixed $path
     * @return String
     */
    public function getRouteId(string $path = ''): string
    {
        if ($this->routeId === null) {
            $parts = [
                sprintf(
                    '%s-%d',
                    $this->getResource()->resource_type_id ?? null,
                    $this->getResource()->resource_id ?? null
                ),
                $path,
            ];

            return sprintf('%s', implode('/', array_filter($parts)));
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

            return sprintf('/%s', implode('/', array_filter($parts)));
        }

        return $this->routePath;
    }

    /**
     * getRouteTitle
     */
    public function getRouteTitle(): ?string
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
        return $this->controller;
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

        return sprintf('controller:%s', $controllerName);
    }

    /**
     * getRouteConfig
     */
    abstract public function getRouteConfig(): array;

    /**
     * getRoutePages
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
            'pages'      => [],
        ];
    }

    /**
     * setResource
     */
    public function setLandingPage(bool $flag): void
    {
        $this->landingPage = $flag;
    }

    /**
     * getResource
     */
    public function getLandingPage(): bool
    {
        return $this->landingPage;
    }

    /**
     * getPageChangeFrequency
     */
    public function getPageChangeFrequency(): ?string
    {
        return $this->changeFrequency;
    }

    /**
     * getPageChangeFrequency
     */
    public function getPriority(): ?string
    {
        return $this->priority;
    }
}
