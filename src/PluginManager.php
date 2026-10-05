<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

use Laminas\ServiceManager\AbstractPluginManager;

/**
 * Plugin manager for workflows, keyed by a resource's "workflow" value.
 *
 * @extends AbstractPluginManager<Workflow\WorkflowInterface>
 *
 * @api
 */
class PluginManager extends AbstractPluginManager
{
    /** @var class-string<Workflow\WorkflowInterface>|null */
    protected $instanceOf = Workflow\WorkflowInterface::class;
}
