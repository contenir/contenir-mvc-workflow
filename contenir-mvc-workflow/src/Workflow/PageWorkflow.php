<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Laminas\Router\Http\Literal;

class PageWorkflow extends AbstractWorkflow
{
    public function getRoutePath(): string
    {
        if ($this->routePath === null) {
            $parts = explode('/', (string) $this->getResource()->slug);
            return sprintf('/%s', join('/', array_filter($parts)));
        }

        return $this->routePath;
    }

    public function getRouteConfig(): array
    {
        $config = [
            'type' => Literal::class,
            'options' => [
                'route' => $this->getRoutePath(),
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
