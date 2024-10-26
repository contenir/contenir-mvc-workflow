<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use function sprintf;

class PageActionWorkflow extends PageWorkflow
{
    public function getRouteConfig(): array
    {
        return [
            'type'    => 'segment',
            'options' => [
                'route'    => sprintf(
                    '%s[/:action]',
                    $this->getRoutePath()
                ),
                'defaults' => [
                    'controller'  => $this->getRouteController(),
                    'action'      => 'index',
                    'resource_id' => $this->getResource()->getPrimaryKeys(),
                ],
            ],
        ];
    }
}
