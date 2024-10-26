<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

interface WorkflowInterface
{
    public function getRouteId();

    public function getRoutePath();

    public function getRouteConfig();

    public function getNavigationConfig();
}
