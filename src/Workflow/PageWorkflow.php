<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Laminas\Router\Http\Literal;

class PageWorkflow extends AbstractWorkflow
{
    public function getRouteConfig(): array
    {
        return [
            'type'    => Literal::class,
            'options' => [
                'route'    => $this->getRoutePath(),
                'defaults' => [
                    'controller'  => $this->getRouteController(),
                    'action'      => 'index',
                    'resource_id' => $this->getResource()->getPrimaryKeys(),
                ],
            ],
        ];
    }
}
