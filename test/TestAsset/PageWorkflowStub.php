<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset;

use Contenir\Mvc\Workflow\Workflow\PageWorkflow;

/**
 * Concrete subclass of PageWorkflow exposing a default controller so that
 * AbstractWorkflow::getResourceId() can be exercised.
 */
class PageWorkflowStub extends PageWorkflow
{
    protected ?string $controller = 'Application\\Controller\\IndexController';
}
