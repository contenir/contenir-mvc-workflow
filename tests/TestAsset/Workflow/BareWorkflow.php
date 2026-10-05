<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use Override;

/**
 * Implements only WorkflowInterface, which the strategy cannot drive.
 */
final class BareWorkflow implements WorkflowInterface
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getNavigationConfig(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRouteConfig(): array
    {
        return [];
    }

    #[Override]
    public function getRouteId(): string
    {
        return 'bare';
    }

    #[Override]
    public function getRoutePath(): string
    {
        return '/bare';
    }
}
