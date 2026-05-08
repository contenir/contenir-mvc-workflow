<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Laminas\Router\Http\Literal;

class ArticleWorkflow extends AbstractArticleWorkflow
{
    protected ?string $segment         = 'post';
    protected array $subPages          = [
        'post' => [
            ['title' => 'Article'],
        ],
    ];
    protected ?string $changeFrequency = 'monthly';
    protected string $priority         = '0.5';

    public function getRouteConfig(): array
    {
        return [
            'type'          => Literal::class,
            'options'       => [
                'route'    => $this->getRoutePath(),
                'defaults' => [
                    'controller'  => $this->getRouteController(),
                    'action'      => 'index',
                    'resource_id' => $this->getResource()->getPrimaryKeys(),
                ],
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
                        'defaults'    => [
                            'action' => 'view',
                        ],
                    ],
                ],
            ],
        ];
    }
}
