<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Resource;

interface ResourceAdapterInterface
{
    public function getWorkflowResources(): iterable;
}
