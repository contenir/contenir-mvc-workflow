<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Workflow\PageWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\PageWorkflowStub;
use ContenirTest\Mvc\Workflow\TestAsset\ResourceStub;
use Laminas\Router\Http\Literal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PageWorkflowTest extends TestCase
{
    public function testGetRoutePathDerivesFromResourceSlug(): void
    {
        $resource = new ResourceStub();
        $resource->setSlug('about/team');

        $workflow = new PageWorkflowStub();
        $workflow->setResource($resource);

        $this->assertSame('/about/team', $workflow->getRoutePath());
    }

    public function testGetRoutePathFiltersEmptySlugSegments(): void
    {
        $resource = new ResourceStub();
        $resource->setSlug('//double//empty');

        $workflow = new PageWorkflowStub();
        $workflow->setResource($resource);

        $this->assertSame('/double/empty', $workflow->getRoutePath());
    }

    public function testGetRoutePathReturnsExplicitPath(): void
    {
        $workflow   = new PageWorkflowStub();
        $reflection = new ReflectionClass($workflow);
        $prop       = $reflection->getProperty('routePath');
        $prop->setAccessible(true);
        $prop->setValue($workflow, '/configured');

        $this->assertSame('/configured', $workflow->getRoutePath());
    }

    public function testGetRouteConfigReturnsLiteralRoute(): void
    {
        $resource = new ResourceStub([
            'resource_type_id' => 1,
            'resource_id'      => 5,
        ]);
        $resource->setSlug('contact');
        $resource->setPrimaryKeys(['resource_id' => 5]);

        $workflow = new PageWorkflowStub();
        $workflow->setResource($resource);

        $this->assertSame(
            [
                'type'    => Literal::class,
                'options' => [
                    'route'    => '/contact',
                    'defaults' => [
                        'controller'  => 'Application\\Controller\\IndexController',
                        'action'      => 'index',
                        'resource_id' => ['resource_id' => 5],
                    ],
                ],
            ],
            $workflow->getRouteConfig()
        );
    }

    public function testBaseClassCanBeInstantiated(): void
    {
        $this->assertInstanceOf(PageWorkflow::class, new PageWorkflow());
    }
}
