<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;

interface ResourceStrategyInterface
{
    public function getNavigationPage(AbstractWorkflow $workflow): array;
}
