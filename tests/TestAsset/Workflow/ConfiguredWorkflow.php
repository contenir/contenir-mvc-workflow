<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\IndexController;
use Override;

/**
 * Concrete AbstractWorkflow whose protected settings are given to the
 * constructor, the way an application subclass would declare them.
 */
final class ConfiguredWorkflow extends AbstractWorkflow
{
    /**
     * @param iterable<array-key, mixed> $workflowConfig
     * @param array<array-key, mixed> $pages
     *
     * @mago-expect lint:excessive-parameter-list One argument per protected setting an application subclass declares.
     */
    public function __construct(
        iterable $workflowConfig = [],
        ?string $controller = IndexController::class,
        ?string $routeId = null,
        ?string $routePath = null,
        ?string $routeTitle = null,
        array $pages = [],
        ?string $changeFrequency = null,
        string $priority = '0.5',
    ) {
        parent::__construct($workflowConfig);

        $this->controller      = $controller;
        $this->routeId         = $routeId;
        $this->routePath       = $routePath;
        $this->routeTitle      = $routeTitle;
        $this->pages           = $pages;
        $this->changeFrequency = $changeFrequency;
        $this->priority        = $priority;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getRouteConfig(): array
    {
        return [
            'type'    => 'literal',
            'options' => [
                'route' => $this->getRoutePath(),
            ],
        ];
    }
}
