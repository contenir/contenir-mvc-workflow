<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Laminas\Router\Http\Literal;

class PageActionWorkflow extends PageWorkflow
{
    public function getRouteConfig(): array
    {
        $config = [
            'type' => 'segment',
            'options' => [
                'route' => sprintf(
                    '%s[/:action]',
                    $this->getRoutePath()
                ),
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
