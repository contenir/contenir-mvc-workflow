<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Laminas\Router\Http\Literal;

class ArticleWorkflow extends AbstractWorkflow
{
    protected $segment  = 'post';
    protected $subPages = [
        'post' => [
            ['title' => 'Article']
        ]
    ];
    protected $changeFrequency = 'monthly';
    protected $priority        = '0.5';

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
            'type'    => Literal::class,
            'options' => [
                'route'    => $this->getRoutePath(),
                'defaults' => [
                    'controller'  => $this->getRouteController(),
                    'action'      => 'index',
                    'resource_id' => $this->getResource()->resource_id
                ]
            ],
            'may_terminate' => true,
            'child_routes'  => [
                $this->segment => [
                    'type'    => 'segment',
                    'options' => [
                        'route'       => '[/:slug]',
                        'constraints' => [
                            'slug' => '[a-zA-Z0-9_-]+',
                        ],
                        'defaults' => [
                            'action' => 'view',
                        ]
                    ]
                ]
            ]
        ];

        return $config;
    }
}
