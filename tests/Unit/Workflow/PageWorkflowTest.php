<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Workflow;

use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Contenir\Mvc\Workflow\Workflow\PageWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\IndexController;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\ResourceFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\IndexPageWorkflow;
use Laminas\Router\Http\Literal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageWorkflow::class)]
#[Group('unit')]
final class PageWorkflowTest extends TestCase
{
    #[Test]
    public function routeIsALiteralToTheIndexAction(): void
    {
        $workflow = new IndexPageWorkflow();
        $workflow->setResource(ResourceFactory::page(
            id: 5,
            slug: 'contact',
        ));

        static::assertSame(
            [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/contact',
                    'defaults' => [
                        'controller'  => IndexController::class,
                        'action'      => 'index',
                        'resource_id' => ['resource_id' => 5],
                    ],
                ],
            ],
            $workflow->getRouteConfig(),
        );
    }

    #[Test]
    public function routeNeedsAController(): void
    {
        $workflow = new PageWorkflow();
        $workflow->setResource(ResourceFactory::page());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workflow ' . PageWorkflow::class . ' has no controller configured');

        $workflow->getRouteConfig();
    }
}
