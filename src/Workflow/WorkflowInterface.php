<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

/**
 * Route and navigation definition for one kind of resource.
 *
 * The strategy also relies on the rest of AbstractWorkflow's public API, so
 * workflows should extend AbstractWorkflow rather than implement this directly.
 *
 * @api
 */
interface WorkflowInterface
{
    /**
     * laminas-navigation page specification.
     *
     * @return array<string, mixed>
     */
    public function getNavigationConfig(): array;

    /**
     * laminas-router route specification.
     *
     * @return array<string, mixed>
     */
    public function getRouteConfig(): array;

    /**
     * Route name.
     */
    public function getRouteId(): string;

    /**
     * Route path, with a leading slash.
     */
    public function getRoutePath(): string;
}
