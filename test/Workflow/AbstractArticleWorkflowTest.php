<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Workflow\AbstractArticleWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\ResourceStub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AbstractArticleWorkflowTest extends TestCase
{
    private function makeWorkflow(): AbstractArticleWorkflow
    {
        return new class extends AbstractArticleWorkflow {
            protected ?string $controller = 'Article\\Controller\\IndexController';

            public function getRouteConfig(): array
            {
                return ['marker' => true];
            }
        };
    }

    public function testGetRoutePathBuildsFromSlug(): void
    {
        $resource = new ResourceStub();
        $resource->setSlug('blog/post');

        $workflow = $this->makeWorkflow();
        $workflow->setResource($resource);

        $this->assertSame('/blog/post', $workflow->getRoutePath());
    }

    public function testGetRoutePathReturnsExplicitPath(): void
    {
        $workflow   = $this->makeWorkflow();
        $reflection = new ReflectionClass(AbstractArticleWorkflow::class);
        $prop       = $reflection->getProperty('routePath');
        $prop->setAccessible(true);
        $prop->setValue($workflow, '/explicit');

        $this->assertSame('/explicit', $workflow->getRoutePath());
    }

    public function testSubclassImplementsRouteConfig(): void
    {
        $workflow = $this->makeWorkflow();
        $this->assertSame(['marker' => true], $workflow->getRouteConfig());
    }
}
