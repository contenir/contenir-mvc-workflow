<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Override;

/**
 * Base for article listings with a child route per article.
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
     */
    #[Override]
    abstract public function getRouteConfig(): array;
}
