<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\PageWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\IndexController;

/**
 * A page workflow as applications declare one: only the controller is set.
 */
final class IndexPageWorkflow extends PageWorkflow
{
    protected ?string $controller = IndexController::class;
}
