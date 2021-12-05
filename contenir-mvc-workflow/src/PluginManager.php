<?php

namespace Contenir\Mvc\Workflow;

use Laminas\ServiceManager\AbstractPluginManager;

class PluginManager extends AbstractPluginManager
{
    protected $instanceOf = Workflow\WorkflowInterface::class;
}
