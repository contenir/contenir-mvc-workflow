<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Laminas\Router\Http\Literal;

class PageActionWorkflow extends AbstractWorkflow
{
    public function getRouteConfig(): array
    {
        $config = [
            'type' => 'segment',
            'options' => [
                'route' => $this->getRoutePath() . '[/:action]',
                'defaults' => [
                    'controller' => $this->getRouteController(),
                    'action' => 'index',
                    'resource_id' => $this->getResource()->resource_id
                ]
            ]
        ];

        return $config;
    }
}
