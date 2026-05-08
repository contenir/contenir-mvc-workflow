<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

abstract class AbstractArticleWorkflow extends AbstractWorkflow
{
    protected ?string $segment         = 'post';
    protected array $subPages          = [
        'post' => [
            ['title' => 'Article'],
        ],
    ];
    protected ?string $changeFrequency = 'monthly';
    protected string $priority         = '0.5';

    abstract public function getRouteConfig(): array;
}
