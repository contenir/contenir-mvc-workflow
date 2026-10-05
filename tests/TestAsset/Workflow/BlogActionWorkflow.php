<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\PageActionWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\BlogController;

/**
 * A page-with-actions workflow as applications declare one.
 */
final class BlogActionWorkflow extends PageActionWorkflow
{
    protected ?string $controller = BlogController::class;
}
