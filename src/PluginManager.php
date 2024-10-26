<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

use Laminas\ServiceManager\AbstractPluginManager;

class PluginManager extends AbstractPluginManager
{
    /** @var string */
    protected $instanceOf = Workflow\WorkflowInterface::class;
}
