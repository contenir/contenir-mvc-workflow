<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;
use Override;

/**
 * A workflow that contributes a navigation page but no route.
 */
final class RoutelessWorkflow extends AbstractWorkflow
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRouteConfig(): array
    {
        return [];
    }
}
