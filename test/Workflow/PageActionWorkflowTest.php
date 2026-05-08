<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Workflow\PageActionWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\ResourceStub;
use PHPUnit\Framework\TestCase;

class PageActionWorkflowTest extends TestCase
{
    public function testGetRouteConfigBuildsSegmentRouteWithOptionalAction(): void
    {
        $resource = new ResourceStub();
        $resource->setSlug('blog');
        $resource->setPrimaryKeys(['resource_id' => 9]);

        $workflow = new class extends PageActionWorkflow {
            protected ?string $controller = 'My\\Controller\\BlogController';
        };
        $workflow->setResource($resource);

        $config = $workflow->getRouteConfig();

        $this->assertSame('segment', $config['type']);
        $this->assertSame('/blog[/:action]', $config['options']['route']);
        $this->assertSame('My\\Controller\\BlogController', $config['options']['defaults']['controller']);
        $this->assertSame('index', $config['options']['defaults']['action']);
        $this->assertSame(['resource_id' => 9], $config['options']['defaults']['resource_id']);
    }
}
