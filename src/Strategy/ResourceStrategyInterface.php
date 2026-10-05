<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;

/**
 * Turns the resource tree into router and navigation configuration.
 *
 * @api
 */
interface ResourceStrategyInterface
{
    /**
     * laminas-navigation page specifications for the whole tree.
     *
     * @return array<array-key, mixed>
     */
    public function getNavigationConfig(): array;

    /**
     * The navigation page specification for one resource's workflow.
     *
     * @return array<string, mixed>
     */
    public function getNavigationPage(AbstractWorkflow $workflow): array;

    /**
     * laminas-router route specifications, keyed by route name.
     *
     * @return array<array-key, mixed>
     */
    public function getRouteConfig(): array;
}
