<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use ContenirTest\Mvc\Workflow\TestAsset\AbstractWorkflowStub;
use ContenirTest\Mvc\Workflow\TestAsset\ResourceStub;
use PHPUnit\Framework\TestCase;
use TypeError;

class AbstractWorkflowTest extends TestCase
{
    public function testImplementsWorkflowInterface(): void
    {
        $this->assertInstanceOf(WorkflowInterface::class, new AbstractWorkflowStub());
    }

    public function testConstructorAppliesConfigForResource(): void
    {
        // No resource is set, so resourceId is null at construction.
        // Config keyed by null falls back to []; nothing should be set.
        $workflow = new AbstractWorkflowStub(['key' => ['title' => 'something']]);

        $this->assertSame([], $workflow->getProtectedProperty('workflowConfig'));
        $this->assertNull($workflow->getProtectedProperty('workflowTitle'));
        $this->assertNull($workflow->getProtectedProperty('workflowDescription'));
    }

    public function testSetConfigAppliesValuesWhenKeyedByResourceId(): void
    {
        $workflow = new AbstractWorkflowStub();
        // Drive the string-key branch: simulate a resource id as a string.
        $workflow->setProtectedProperty('resourceId', 'page-1');

        $workflow->setConfig([
            'page-1' => [
                'title'       => 'Page Title',
                'description' => 'Page Description',
            ],
        ]);

        $this->assertSame(
            ['title' => 'Page Title', 'description' => 'Page Description'],
            $workflow->getProtectedProperty('workflowConfig')
        );
        $this->assertSame('Page Title', $workflow->getProtectedProperty('workflowTitle'));
        $this->assertSame('Page Description', $workflow->getProtectedProperty('workflowDescription'));
    }

    public function testSetResourcePopulatesResourceIdAndWorkflowId(): void
    {
        $resource = new ResourceStub();
        $resource->setPrimaryKeys(['id' => 42]);
        $resource->workflow = 'article';

        $workflow = new AbstractWorkflowStub();
        $workflow->setResource($resource);

        $this->assertSame($resource, $workflow->getResource());
        $this->assertSame(['id' => 42], $workflow->getProtectedProperty('resourceId'));
        $this->assertSame('article', $workflow->getWorkflowId());
    }

    public function testSetResourceFallsBackToPageWorkflowWhenWorkflowMissing(): void
    {
        $resource = new ResourceStub();
        unset($resource->workflow);

        $workflow = new AbstractWorkflowStub();
        $workflow->setResource($resource);

        $this->assertSame('page', $workflow->getWorkflowId());
    }

    public function testGetResourceReturnsNullByDefault(): void
    {
        $workflow = new AbstractWorkflowStub();
        $this->assertNull($workflow->getResource());
    }

    public function testGetRouteIdComputesFromResourceTypeAndId(): void
    {
        $resource = new ResourceStub([
            'resource_type_id' => 7,
            'resource_id'      => 99,
        ]);
        $workflow = new AbstractWorkflowStub();
        $workflow->setResource($resource);

        $this->assertSame('7-99', $workflow->getRouteId());
    }

    public function testGetRouteIdAppendsPath(): void
    {
        $resource = new ResourceStub([
            'resource_type_id' => 7,
            'resource_id'      => 99,
        ]);
        $workflow = new AbstractWorkflowStub();
        $workflow->setResource($resource);

        $this->assertSame('7-99/sub', $workflow->getRouteId('sub'));
    }

    public function testGetRouteIdReturnsConfiguredRouteIdWhenSet(): void
    {
        $workflow = new AbstractWorkflowStub();
        $workflow->setProtectedProperty('routeId', 'preset/route');

        $this->assertSame('preset/route', $workflow->getRouteId());
        $this->assertSame('preset/route', $workflow->getRouteId('ignored'));
    }

    public function testGetRoutePathReturnsConfiguredPathWhenSet(): void
    {
        $workflow = new AbstractWorkflowStub();
        $workflow->setProtectedProperty('routePath', '/explicit/path');

        $this->assertSame('/explicit/path', $workflow->getRoutePath());
    }

    public function testGetRoutePathBuildsFromStringResourceId(): void
    {
        $workflow = new AbstractWorkflowStub();
        $workflow->setProtectedProperty('resourceId', 'parent/child');

        $this->assertSame('/parent/child', $workflow->getRoutePath());
    }

    public function testGetRoutePathRaisesTypeErrorWhenResourceIdIsArray(): void
    {
        // setResource() always assigns the array returned by getPrimaryKeys()
        // to resourceId. AbstractWorkflow::getRoutePath() then explode()s it,
        // which is invalid. Subclasses (PageWorkflow, ArticleWorkflow) avoid
        // this by overriding getRoutePath(). This test pins the existing
        // behaviour so future refactors notice the regression.
        $workflow = new AbstractWorkflowStub();
        $workflow->setProtectedProperty('resourceId', ['k' => 'v']);

        $this->expectException(TypeError::class);
        $workflow->getRoutePath();
    }

    public function testGetRouteTitleDefaultsToNull(): void
    {
        $workflow = new AbstractWorkflowStub();
        $this->assertNull($workflow->getRouteTitle());

        $workflow->setProtectedProperty('routeTitle', 'Section');
        $this->assertSame('Section', $workflow->getRouteTitle());
    }

    public function testGetRouteControllerReturnsConfiguredController(): void
    {
        $workflow = new AbstractWorkflowStub();
        $this->assertSame('Application\\Controller\\IndexController', $workflow->getRouteController());
    }

    public function testGetResourceIdNormalisesControllerName(): void
    {
        $workflow = new AbstractWorkflowStub();
        $this->assertSame('controller:application.index', $workflow->getResourceId());
    }

    public function testGetResourceIdHandlesNestedNamespace(): void
    {
        $workflow = new AbstractWorkflowStub();
        $workflow->setProtectedProperty('controller', 'My\\Module\\Controller\\AdminController');

        $this->assertSame('controller:my\\module.admin', $workflow->getResourceId());
    }

    public function testGetRoutePagesReturnsConfiguredPages(): void
    {
        $workflow = new AbstractWorkflowStub();
        $this->assertSame([], $workflow->getRoutePages());

        $workflow->setProtectedProperty('pages', ['p' => []]);
        $this->assertSame(['p' => []], $workflow->getRoutePages());
    }

    public function testGetNavigationConfigReportsDefaults(): void
    {
        $resource = new ResourceStub([
            'resource_type_id' => 7,
            'resource_id'      => 99,
        ]);
        $workflow = new AbstractWorkflowStub();
        $workflow->setResource($resource);
        $workflow->setProtectedProperty('workflowTitle', 'My Title');

        $this->assertSame(
            [
                'label'      => 'My Title',
                'route'      => '7-99',
                'changefreq' => null,
                'priority'   => '0.5',
                'visible'    => true,
                'pages'      => [],
            ],
            $workflow->getNavigationConfig()
        );
    }

    public function testLandingPageFlagDefaultsAndMutates(): void
    {
        $workflow = new AbstractWorkflowStub();
        $this->assertFalse($workflow->getLandingPage());

        $workflow->setLandingPage(true);
        $this->assertTrue($workflow->getLandingPage());

        $workflow->setLandingPage(false);
        $this->assertFalse($workflow->getLandingPage());
    }

    public function testChangeFrequencyAndPriorityDefaults(): void
    {
        $workflow = new AbstractWorkflowStub();
        $this->assertNull($workflow->getPageChangeFrequency());
        $this->assertSame('0.5', $workflow->getPriority());

        $workflow->setProtectedProperty('changeFrequency', 'weekly');
        $workflow->setProtectedProperty('priority', '0.9');

        $this->assertSame('weekly', $workflow->getPageChangeFrequency());
        $this->assertSame('0.9', $workflow->getPriority());
    }

    public function testGetRouteConfigReturnsArrayFromSubclass(): void
    {
        $workflow = new AbstractWorkflowStub();
        $workflow->setProtectedProperty('routePath', '/x');

        $this->assertSame(
            ['type' => 'literal', 'options' => ['route' => '/x']],
            $workflow->getRouteConfig()
        );
    }
}
