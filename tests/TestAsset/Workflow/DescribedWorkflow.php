<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;
use Override;

/**
 * A workflow that publishes its configured description, the way an
 * application subclass reads the protected workflow settings.
 */
final class DescribedWorkflow extends AbstractWorkflow
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getNavigationConfig(): array
    {
        return [...parent::getNavigationConfig(), 'description' => $this->workflowDescription];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRouteConfig(): array
    {
        return [];
    }
}
