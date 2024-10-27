<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use function array_filter;
use function explode;
use function implode;
use function sprintf;

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

    public function getRoutePath(): string
    {
        if ($this->routePath === null) {
            $parts = explode('/', $this->getResource()->getSlug());
            return sprintf('/%s', implode('/', array_filter($parts)));
        }

        return $this->routePath;
    }

    abstract public function getRouteConfig(): array;
}
