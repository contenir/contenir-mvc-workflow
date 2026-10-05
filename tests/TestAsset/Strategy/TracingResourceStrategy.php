<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Strategy;

use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use Contenir\Mvc\Workflow\Strategy\AbstractResourceStrategy;
use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use Override;

/**
 * An application strategy that decorates every protected hook, recording
 * each call before handing over to the base implementation.
 */
final class TracingResourceStrategy extends AbstractResourceStrategy
{
    /** @var list<string> */
    public array $trace = [];

    #[Override]
    protected function build(): void
    {
        $this->trace[] = 'build';

        parent::build();
    }

    /**
     * @param array<array-key, mixed> $routePages
     *
     * @return list<array<string, mixed>>
     */
    #[Override]
    protected function getNavigationSubPage(
        ResourceInterface $resource,
        string $parentRouteId,
        string $routeId,
        array $routePages,
    ): array {
        $this->trace[] = "sub-page {$parentRouteId}/{$routeId}";

        return parent::getNavigationSubPage($resource, $parentRouteId, $routeId, $routePages);
    }

    #[Override]
    protected function getResourceWorkFlow(ResourceInterface $resource): WorkflowInterface
    {
        $workflow      = parent::getResourceWorkFlow($resource);
        $this->trace[] = "workflow {$workflow->getRouteId()}";

        return $workflow;
    }

    /**
     * @param iterable<ResourceInterface> $resources
     * @param array<array-key, mixed> $pages
     *
     * @return array<array-key, mixed>
     */
    #[Override]
    protected function process(iterable $resources, array $pages = []): array
    {
        $this->trace[] = 'process';

        return parent::process($resources, $pages);
    }
}
