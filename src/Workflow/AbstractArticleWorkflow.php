<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Laminas\Router\Http\Literal;
use Override;

/**
 * Base for article listings: a literal route to the index action, with a
 * "[/:slug]" child route (named after $segment) to the view action.
 *
 * @api
 */
abstract class AbstractArticleWorkflow extends AbstractWorkflow
{
    protected ?string $segment = 'post';

    /** @var array<string, list<array<string, mixed>>> */
    protected array $subPages = [
        'post' => [
            ['title' => 'Article'],
        ],
    ];

    protected ?string $changeFrequency = 'monthly';
    protected string  $priority        = '0.5';

    /**
     * @return array<string, mixed>
     *
     * @throws RuntimeException When no resource or controller is set.
     */
    #[Override]
    public function getRouteConfig(): array
    {
        return [
            'type'          => Literal::class,
            'options'       => [
                'route'    => $this->getRoutePath(),
                'defaults' => [
                    'controller'  => $this->getRouteController(),
                    'action'      => 'index',
                    'resource_id' => $this->requireResource()->getPrimaryKeys(),
                ],
            ],
            'may_terminate' => true,
            'child_routes'  => [
                (string) $this->segment => [
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
