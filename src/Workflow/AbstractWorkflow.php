<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use Contenir\Mvc\Workflow\Resource\ResourceProperty;
use Override;

use function array_filter;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function iterator_to_array;
use function sprintf;
use function str_replace;
use function strtolower;

/**
 * Base workflow: derives the route name, path and navigation page of a
 * resource. Subclasses set $controller and implement getRouteConfig().
 *
 * @api
 *
 * @mago-expect lint:cyclomatic-complexity 1.x public API kept intact; splitting the class is a suggested follow-up.
 * @mago-expect lint:too-many-methods 1.x public API kept intact; splitting the class is a suggested follow-up.
 * @mago-expect lint:too-many-properties Subclasses configure workflows by overriding these protected properties.
 */
abstract class AbstractWorkflow implements WorkflowInterface
{
    private ?ResourceInterface $resource = null;

    /** @var array<array-key, mixed> */
    protected array $workflowConfig = [];

    /** @var array<array-key, mixed> */
    protected array $rawWorkflowConfig = [];

    protected ?string $workflowTitle       = null;
    protected ?string $workflowId          = null;
    protected ?string $workflowDescription = null;
    protected ?string $controller          = null;
    protected ?string $routeId             = null;
    protected ?string $routePath           = null;
    protected ?string $routeTitle          = null;
    protected ?string $segment             = null;

    /** @var string|array<array-key, mixed>|null */
    protected string|array|null $resourceId = null;

    /** @var array<array-key, mixed> */
    protected array $pages = [];

    protected bool    $landingPage     = false;
    protected ?string $changeFrequency = null;
    protected string  $priority        = '0.5';

    /**
     * @param iterable<array-key, mixed> $workflowConfig Per-workflow settings, keyed by workflow id.
     */
    public function __construct(iterable $workflowConfig = [])
    {
        $this->setConfig($workflowConfig);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    abstract public function getRouteConfig(): array;

    public function getLandingPage(): bool
    {
        return $this->landingPage;
    }

    /**
     * @return array{label: string|null, route: string, changefreq: string|null, priority: string|null, visible: true, pages: array<never, never>}
     */
    #[Override]
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

    public function getPageChangeFrequency(): ?string
    {
        return $this->changeFrequency;
    }

    /**
     * @mago-expect analysis:overly-wide-return-type Kept nullable so 1.x subclasses overriding it stay compatible.
     */
    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function getResource(): ?ResourceInterface
    {
        return $this->resource;
    }

    /**
     * ACL resource name derived from the controller, e.g.
     * "Application\Controller\IndexController" becomes "controller:application.index".
     */
    public function getResourceId(): string
    {
        $controllerName = $this->controller ?? '';
        $controllerName = str_replace(
            search: '\\Controller\\',
            replace: '.',
            subject: $controllerName,
        );
        $controllerName = str_replace(
            search: 'Controller',
            replace: '',
            subject: $controllerName,
        );
        $controllerName = strtolower($controllerName);

        return sprintf('controller:%s', $controllerName);
    }

    /**
     * @throws RuntimeException When the workflow has no controller.
     */
    public function getRouteController(): string
    {
        return (
            $this->controller ?? throw new RuntimeException(sprintf(
                'Workflow %s has no controller configured',
                static::class,
            ))
        );
    }

    /**
     * The configured route name, or "<resource_type_id>-<resource_id>" with
     * $path appended.
     */
    #[Override]
    public function getRouteId(string $path = ''): string
    {
        if (null !== $this->routeId) {
            return $this->routeId;
        }

        $typeId   = null;
        $id       = null;
        $resource = $this->getResource();
        if (null !== $resource) {
            $typeId = ResourceProperty::string($resource, 'resource_type_id');
            $id     = ResourceProperty::string($resource, 'resource_id');
        }

        $parts = [sprintf('%s-%d', $typeId, $id), $path];

        return implode('/', array_filter($parts));
    }

    /**
     * Child route pages, keyed by child route name.
     *
     * @return array<array-key, mixed>
     */
    public function getRoutePages(): array
    {
        return $this->pages;
    }

    /**
     * The configured route path, or the resource's slug with a leading slash.
     *
     * @throws RuntimeException When no path is configured and no resource has been set.
     */
    #[Override]
    public function getRoutePath(): string
    {
        if (null !== $this->routePath) {
            return $this->routePath;
        }

        $parts = explode('/', $this->requireResource()->getSlug());

        return sprintf('/%s', implode('/', array_filter($parts)));
    }

    public function getRouteTitle(): ?string
    {
        return $this->routeTitle;
    }

    /**
     * @throws RuntimeException When no resource has been set.
     */
    public function getWorkflowId(): string
    {
        return (
            $this->workflowId ?? throw new RuntimeException(sprintf(
                'Workflow %s has no workflow id until a resource is set',
                static::class,
            ))
        );
    }

    /**
     * @param iterable<array-key, mixed> $config Per-workflow settings, keyed by workflow id.
     */
    public function setConfig(iterable $config): void
    {
        $this->rawWorkflowConfig = is_array($config) ? $config : iterator_to_array($config);

        $this->resolveWorkflowConfig();
    }

    public function setLandingPage(bool $flag): void
    {
        $this->landingPage = $flag;
    }

    public function setResource(ResourceInterface $resource): void
    {
        $this->resource = $resource;

        $this->resourceId = $resource->getPrimaryKeys();
        $this->workflowId = ResourceProperty::string($resource, 'workflow') ?? 'page';

        $this->resolveWorkflowConfig();
    }

    /**
     * @throws RuntimeException When no resource has been set.
     */
    protected function requireResource(): ResourceInterface
    {
        return (
            $this->resource ?? throw new RuntimeException(sprintf(
                'Workflow %s has no resource set',
                static::class,
            ))
        );
    }

    /**
     * @mago-expect analysis:mixed-assignment Workflow configuration is untyped input; narrowed here.
     */
    private function resolveWorkflowConfig(): void
    {
        if (null === $this->workflowId) {
            return;
        }

        $config      = $this->rawWorkflowConfig[$this->workflowId] ?? null;
        $title       = is_array($config) ? $config['title'] ?? null : null;
        $description = is_array($config) ? $config['description'] ?? null : null;

        $this->workflowConfig      = is_array($config) ? $config : [];
        $this->workflowTitle       = is_string($title) ? $title : $this->workflowTitle;
        $this->workflowDescription = is_string($description) ? $description : $this->workflowDescription;
    }
}
