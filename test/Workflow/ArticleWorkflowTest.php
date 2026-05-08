<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Workflow\ArticleWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\ResourceStub;
use Laminas\Router\Http\Literal;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ArticleWorkflowTest extends TestCase
{
    private function makeWorkflow(): ArticleWorkflow
    {
        return new class extends ArticleWorkflow {
            protected ?string $controller = 'Article\\Controller\\IndexController';
        };
    }

    public function testGetRoutePathDerivesFromSlug(): void
    {
        $resource = new ResourceStub();
        $resource->setSlug('news/2024/january');

        $workflow = $this->makeWorkflow();
        $workflow->setResource($resource);

        $this->assertSame('/news/2024/january', $workflow->getRoutePath());
    }

    public function testGetRoutePathReturnsExplicitPath(): void
    {
        $workflow   = $this->makeWorkflow();
        $reflection = new ReflectionClass(ArticleWorkflow::class);
        $prop       = $reflection->getProperty('routePath');
        $prop->setAccessible(true);
        $prop->setValue($workflow, '/articles');

        $this->assertSame('/articles', $workflow->getRoutePath());
    }

    public function testGetRouteConfigBuildsRouteWithChildSegment(): void
    {
        $resource = new ResourceStub();
        $resource->setSlug('news');
        $resource->setPrimaryKeys(['resource_id' => 17]);

        $workflow = $this->makeWorkflow();
        $workflow->setResource($resource);

        $config = $workflow->getRouteConfig();

        $this->assertSame(Literal::class, $config['type']);
        $this->assertSame('/news', $config['options']['route']);
        $this->assertSame('Article\\Controller\\IndexController', $config['options']['defaults']['controller']);
        $this->assertSame('index', $config['options']['defaults']['action']);
        $this->assertSame(['resource_id' => 17], $config['options']['defaults']['resource_id']);
        $this->assertTrue($config['may_terminate']);

        $this->assertArrayHasKey('post', $config['child_routes']);
        $child = $config['child_routes']['post'];
        $this->assertSame('segment', $child['type']);
        $this->assertSame('[/:slug]', $child['options']['route']);
        $this->assertSame('[a-zA-Z0-9_-]+', $child['options']['constraints']['slug']);
        $this->assertSame('view', $child['options']['defaults']['action']);
    }

    public function testInheritsArticleDefaults(): void
    {
        $workflow = $this->makeWorkflow();
        $this->assertSame('monthly', $workflow->getPageChangeFrequency());
        $this->assertSame('0.5', $workflow->getPriority());
    }
}
