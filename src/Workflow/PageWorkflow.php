<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Laminas\Router\Http\Literal;
use Override;

/**
 * A single page: a literal route to the controller's index action.
 *
 * @api
 */
class PageWorkflow extends AbstractWorkflow
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
            'type'    => Literal::class,
            'options' => [
                'route'    => $this->getRoutePath(),
                'defaults' => [
                    'controller'  => $this->getRouteController(),
                    'action'      => 'index',
                    'resource_id' => $this->requireResource()->getPrimaryKeys(),
                ],
            ],
        ];
    }
}
