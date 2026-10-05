<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Resource;

/**
 * Supplies the root resources of the site tree to the strategy.
 *
 * @api
 */
interface ResourceAdapterInterface
{
    /**
     * Top-level resources; each one's "children" property holds the next level.
     *
     * @return iterable<ResourceInterface>
     */
    public function getWorkflowResources(): iterable;
}
