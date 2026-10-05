<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Override;

use function sprintf;

/**
 * A page whose controller has several actions: a segment route with an
 * optional "/:action" suffix.
 *
 * @api
 */
class PageActionWorkflow extends PageWorkflow
{
    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException When no resource or controller is set.
     */
    #[Override]
    public function getRouteConfig(): array
    {
        return [
            'type'    => 'segment',
            'options' => [
                'route'    => sprintf(
                    '%s[/:action]',
                    $this->getRoutePath(),
                ),
                'defaults' => [
                    'controller'  => $this->getRouteController(),
                    'action'      => 'index',
                    'resource_id' => $this->requireResource()->getPrimaryKeys(),
                ],
            ],
        ];
    }
}
