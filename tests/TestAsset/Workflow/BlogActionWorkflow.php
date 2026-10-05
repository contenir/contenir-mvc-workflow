<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractPageActionWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\BlogController;

/**
 * A page-with-actions workflow as applications declare one.
 */
final class BlogActionWorkflow extends AbstractPageActionWorkflow
{
    protected ?string $controller = BlogController::class;
}
