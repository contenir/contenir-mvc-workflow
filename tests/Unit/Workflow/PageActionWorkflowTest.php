<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractPageActionWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\BlogController;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\ResourceFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\BlogActionWorkflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractPageActionWorkflow::class)]
#[Group('unit')]
final class PageActionWorkflowTest extends TestCase
{
    #[Test]
    public function routeIsASegmentWithAnOptionalAction(): void
    {
        $workflow = new BlogActionWorkflow();
        $workflow->setResource(ResourceFactory::page(
            id: 9,
            slug: 'blog',
        ));

        static::assertSame(
            [
                'type'    => 'segment',
                'options' => [
                    'route'    => '/blog[/:action]',
                    'defaults' => [
                        'controller'  => BlogController::class,
                        'action'      => 'index',
                        'resource_id' => ['resource_id' => 9],
                    ],
                ],
            ],
            $workflow->getRouteConfig(),
        );
    }
}
