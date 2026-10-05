<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Workflow;

use Contenir\Mvc\Workflow\Workflow\ArticleWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\NewsController;

/**
 * An article workflow as applications declare one.
 */
final class NewsWorkflow extends ArticleWorkflow
{
    protected ?string $controller = NewsController::class;

    protected ?string $changeFrequency = 'weekly';

    protected string $priority = '1.0';
}
