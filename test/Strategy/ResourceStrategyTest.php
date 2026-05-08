<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\MetadataResourceStub;
use ContenirTest\Mvc\Workflow\TestAsset\ResourceStub;
use DateTimeImmutable;
use InvalidArgumentException;
use Laminas\Cache\Storage\StorageInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use function array_merge;

class ResourceStrategyTest extends TestCase
{
    /** @var PluginManager&MockObject */
    private $pluginManager;
    /** @var ResourceAdapterInterface&MockObject */
    private $repository;
    /** @var StorageInterface&MockObject */
    private $cache;

    protected function setUp(): void
    {
        $this->pluginManager = $this->createMock(PluginManager::class);
        $this->repository    = $this->createMock(ResourceAdapterInterface::class);
        $this->cache         = $this->createMock(StorageInterface::class);
    }

    private function makeStrategy(iterable $options = []): ResourceStrategy
    {
        // Always provide a cache via options so the strategy is fully wired.
        $options = array_merge(['cache' => $this->cache], (array) $options);

        return new ResourceStrategy($this->pluginManager, $this->repository, $options);
    }

    public function testImplementsStrategyInterface(): void
    {
        $strategy = $this->makeStrategy();
        $this->assertInstanceOf(ResourceStrategyInterface::class, $strategy);
    }

    public function testConstructorAcceptsCollaboratorsAndOptions(): void
    {
        $strategy = $this->makeStrategy(['cache_key' => 'my-key']);

        $this->assertSame($this->pluginManager, $strategy->getPluginManager());
        $this->assertSame($this->repository, $strategy->getRepository());
        $this->assertSame($this->cache, $strategy->getCache());
    }

    public function testSetOptionsRoutesToSetterWhenAvailable(): void
    {
        $strategy = $this->makeStrategy();
        $newCache = $this->createMock(StorageInterface::class);

        $strategy->setOptions(['cache' => $newCache]);
        $this->assertSame($newCache, $strategy->getCache());
    }

    public function testSetOptionsStoresKnownOptions(): void
    {
        $strategy = $this->makeStrategy();
        $strategy->setOptions(['cache_key' => 'custom-key', 'use_parent_as_landing_page' => true]);

        // Drive the build flow to verify cache_key was applied.
        $this->cache->expects($this->once())
            ->method('hasItem')
            ->with('custom-key')
            ->willReturn(true);
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with('custom-key')
            ->willReturn(['route' => [], 'navigation' => ['cached']]);

        $this->assertSame(['cached'], $strategy->getNavigationConfig());
    }

    public function testSetOptionsThrowsWhenOptionUnknown(): void
    {
        $strategy = $this->makeStrategy();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method setUnknownOption() does not exist');

        $strategy->setOptions(['unknown_option' => 'value']);
    }

    public function testSetOptionsReturnsSelf(): void
    {
        $strategy = $this->makeStrategy();
        $this->assertSame($strategy, $strategy->setOptions([]));
    }

    public function testPluginManagerSetterReplacesInstance(): void
    {
        $strategy = $this->makeStrategy();
        $other    = $this->createMock(PluginManager::class);
        $strategy->setPluginManager($other);
        $this->assertSame($other, $strategy->getPluginManager());
    }

    public function testRepositorySetterReplacesInstance(): void
    {
        $strategy = $this->makeStrategy();
        $other    = $this->createMock(ResourceAdapterInterface::class);
        $strategy->setRepository($other);
        $this->assertSame($other, $strategy->getRepository());
    }

    public function testNavigationConfigSetterStoresValueDirectly(): void
    {
        $strategy = $this->makeStrategy();
        $strategy->setNavigationConfig([['label' => 'Home']]);

        // build() will overwrite from cache if hasItem returns true; ensure it
        // is consulted by stubbing the storage to return the seeded data.
        $this->cache->method('hasItem')->willReturn(true);
        $this->cache->method('getItem')->willReturn([
            'route'      => [],
            'navigation' => [['label' => 'Home']],
        ]);

        $this->assertSame([['label' => 'Home']], $strategy->getNavigationConfig());
    }

    public function testRouteConfigSetterAndGetterRoundTrip(): void
    {
        $strategy = $this->makeStrategy();
        $strategy->setRouteConfig(['the-route' => []]);

        $this->cache->method('hasItem')->willReturn(true);
        $this->cache->method('getItem')->willReturn([
            'route'      => ['the-route' => []],
            'navigation' => [],
        ]);

        $this->assertSame(['the-route' => []], $strategy->getRouteConfig());
    }

    public function testBuildPopulatesCacheOnFirstCall(): void
    {
        $resource = new ResourceStub([
            'resource_type_id' => 1,
            'resource_id'      => 1,
            'visible'          => true,
            'children'         => [],
        ]);
        $resource->setSlug('home');
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('1-1');
        $workflow->method('getRouteConfig')->willReturn(['type' => 'literal']);
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('controller:home');
        $workflow->method('getPageChangeFrequency')->willReturn('daily');
        $workflow->method('getPriority')->willReturn('1.0');
        $workflow->method('getLandingPage')->willReturn(false);

        $this->pluginManager->expects($this->once())
            ->method('build')
            ->with('page')
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('setResource')
            ->with($resource);

        $this->repository->expects($this->once())
            ->method('getWorkflowResources')
            ->willReturn([$resource]);

        $this->cache->expects($this->once())
            ->method('hasItem')
            ->with('ResourceStrategyCache')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('setItem')
            ->with(
                'ResourceStrategyCache',
                $this->callback(function (array $data) {
                    return isset($data['route']['1-1'])
                        && $data['route']['1-1'] === ['type' => 'literal']
                        && $data['navigation'][0]['label'] === 'Default Title';
                })
            );
        // getItem should NOT be called because hasItem returned false.
        $this->cache->expects($this->never())->method('getItem');

        $strategy   = $this->makeStrategy();
        $navigation = $strategy->getNavigationConfig();

        $this->assertCount(1, $navigation);
        $this->assertSame('Default Title', $navigation[0]['label']);
        $this->assertSame('1-1', $navigation[0]['route']);
        $this->assertNull($navigation[0]['lastmod']);
        $this->assertSame('daily', $navigation[0]['changefreq']);
        $this->assertSame('1.0', $navigation[0]['priority']);
        $this->assertTrue($navigation[0]['visible']);
        $this->assertSame('controller:home', $navigation[0]['resource']);
        $this->assertSame([], $navigation[0]['pages']);
    }

    public function testBuildReadsFromCacheWhenHit(): void
    {
        $cached = [
            'route'      => ['existing' => []],
            'navigation' => [['label' => 'Cached']],
        ];

        $this->cache->expects($this->once())->method('hasItem')->willReturn(true);
        $this->cache->expects($this->once())->method('getItem')->willReturn($cached);
        $this->cache->expects($this->never())->method('setItem');

        $this->repository->expects($this->never())->method('getWorkflowResources');

        $strategy = $this->makeStrategy();
        $this->assertSame([['label' => 'Cached']], $strategy->getNavigationConfig());
    }

    public function testGetRouteConfigPopulatesFromBuild(): void
    {
        $cached = [
            'route'      => ['the-route' => ['type' => 'literal']],
            'navigation' => [],
        ];

        $this->cache->method('hasItem')->willReturn(true);
        $this->cache->method('getItem')->willReturn($cached);

        $strategy = $this->makeStrategy();
        $this->assertSame(['the-route' => ['type' => 'literal']], $strategy->getRouteConfig());
    }

    public function testGetNavigationPagePrefersTitleShort(): void
    {
        $resource = new ResourceStub([
            'title'       => 'Long Title',
            'title_short' => 'Short',
            'visible'     => true,
        ]);
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('1');
        $workflow->method('getRouteTitle')->willReturn('Section');
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertSame('Short', $page['label']);
    }

    public function testGetNavigationPageFormatsLastModifiedFromMetadataResource(): void
    {
        $modified               = new DateTimeImmutable('2024-01-02 03:04:05');
        $resource               = new MetadataResourceStub();
        $resource->title        = 'Title';
        $resource->metaModified = $modified;
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('1');
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertSame('2024-01-02 03:04:05', $page['lastmod']);
    }

    public function testGetNavigationPageMetadataResourceWithNullModifiedLeavesLastmodNull(): void
    {
        $resource               = new MetadataResourceStub();
        $resource->title        = 'Title';
        $resource->metaModified = null; // implements MetadataInterface but returns null
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('1');
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertNull($page['lastmod']);
    }

    public function testGetNavigationPageWorkflowLandingPageWithEmptyRoutePagesUsesRouteMatch(): void
    {
        // Drives the >1 routePages branch but with all entries empty so the
        // foreach skips and useRouteMatch stays true on the landing page.
        $resource = new ResourceStub();
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn('Landing');
        // count > 1, but no inner page entries.
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(true);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        // count($routePages) === 0 disqualifies the workflow-landing branch
        // (count must be > 1). Falls through with hasLandingPage=false.
        $this->assertSame([], $page['pages']);
    }

    public function testGetNavigationSubPageFallsBackToResourceTitleWhenAllElseMissing(): void
    {
        $resource = new ResourceStub([
            'title'       => 'Resource Title',
            'title_short' => null,
        ]);
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([
            'sub' => [['params' => ['p' => 1]]], // no 'title' and no title_short
        ]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertSame('Resource Title', $page['pages'][0]['label']);
    }

    public function testGetNavigationPageBuildsLandingPageWhenWorkflowFlagSet(): void
    {
        $resource = new ResourceStub([
            'title'       => 'Resource Title',
            'title_short' => null,
        ]);
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn('Landing Title');
        $workflow->method('getRoutePages')->willReturn([
            'foo' => [
                ['title' => 'Foo Page', 'params' => ['x' => 1]],
            ],
            'bar' => [
                ['title' => 'Bar Page'],
            ],
        ]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(true);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertCount(1, $page['pages']);
        $landing = $page['pages'][0];
        $this->assertSame('Landing Title', $landing['label']);
        $this->assertFalse($landing['useRouteMatch']);
        $this->assertCount(2, $landing['pages']);
        $this->assertSame('Foo Page', $landing['pages'][0]['label']);
        $this->assertSame('parent/foo', $landing['pages'][0]['route']);
        $this->assertSame(['x' => 1], $landing['pages'][0]['params']);
    }

    public function testGetNavigationPageLandingPageWithoutSubpagesUsesRouteMatch(): void
    {
        $resource = new ResourceStub();
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn('My Title');
        // Two route page sets (with string keys) so count > 1, with empty arrays
        // — this drives the "landing page" branch but skips the subpage iteration.
        $workflow->method('getRoutePages')->willReturn(['a' => [], 'b' => []]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(true);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertCount(1, $page['pages']);
        $this->assertSame('My Title', $page['pages'][0]['label']);
        $this->assertFalse($page['pages'][0]['useRouteMatch']);
    }

    public function testGetNavigationPageWithoutLandingPageEmitsSubpagesDirectly(): void
    {
        $resource = new ResourceStub();
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([
            'sub' => [
                ['title' => 'Sub Page'],
            ],
        ]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertFalse($page['useRouteMatch']);
        $this->assertCount(1, $page['pages']);
        $this->assertSame('Sub Page', $page['pages'][0]['label']);
        $this->assertSame('parent/sub', $page['pages'][0]['route']);
    }

    public function testGetNavigationPageUsesParentAsLandingPageWhenOptionSetAndChildrenExist(): void
    {
        $childResource = new ResourceStub();
        $childResource->setPrimaryKeys(['resource_id' => 2]);

        $resource = new ResourceStub([
            'children' => [$childResource],
        ]);
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn('Title');
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy(['use_parent_as_landing_page' => true]);
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertCount(1, $page['pages']);
        $this->assertSame('Title', $page['pages'][0]['label']);
        $this->assertTrue($page['pages'][0]['useRouteMatch']);
    }

    public function testGetNavigationPageProcessesNestedRoutePagesAndLandingTitles(): void
    {
        $resource = new ResourceStub();
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([
            'a' => [
                [
                    'title'        => 'A Top',
                    'landingTitle' => 'A Landing',
                    'pages'        => [
                        'b' => [['title' => 'B Page']],
                    ],
                ],
            ],
        ]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertCount(1, $page['pages']);
        $sub = $page['pages'][0];
        $this->assertSame('A Top', $sub['label']);
        $this->assertSame('parent/a', $sub['route']);
        $this->assertCount(2, $sub['pages']);
        // First entry is the landing-title clone, second is the nested b/page.
        $this->assertSame('A Landing', $sub['pages'][0]['label']);
        $this->assertSame('B Page', $sub['pages'][1]['label']);
        $this->assertSame('parent/a/b', $sub['pages'][1]['route']);
    }

    public function testGetNavigationPageFallsBackToTitleWhenNoRoutePageTitleProvided(): void
    {
        $resource = new ResourceStub([
            'title_short' => 'TS',
        ]);
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('parent');
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([
            'sub' => [['params' => ['p' => 1]]],
        ]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $strategy = $this->makeStrategy();
        $page     = $strategy->getNavigationPage($workflow);

        $this->assertSame('TS', $page['pages'][0]['label']);
    }

    public function testProcessRecursesIntoChildren(): void
    {
        $childResource = new ResourceStub([
            'resource_type_id' => 1,
            'resource_id'      => 2,
        ]);
        $childResource->setPrimaryKeys(['resource_id' => 2]);

        $parentResource = new ResourceStub([
            'resource_type_id' => 1,
            'resource_id'      => 1,
            'children'         => [$childResource],
        ]);
        $parentResource->setPrimaryKeys(['resource_id' => 1]);

        $childWorkflow = $this->createMock(AbstractWorkflow::class);
        $childWorkflow->method('getResource')->willReturn($childResource);
        $childWorkflow->method('getRouteId')->willReturn('1-2');
        $childWorkflow->method('getRouteConfig')->willReturn(['type' => 'segment']);
        $childWorkflow->method('getRouteTitle')->willReturn(null);
        $childWorkflow->method('getRoutePages')->willReturn([]);
        $childWorkflow->method('getResourceId')->willReturn('child');
        $childWorkflow->method('getPageChangeFrequency')->willReturn(null);
        $childWorkflow->method('getPriority')->willReturn('0.5');
        $childWorkflow->method('getLandingPage')->willReturn(false);

        $parentWorkflow = $this->createMock(AbstractWorkflow::class);
        $parentWorkflow->method('getResource')->willReturn($parentResource);
        $parentWorkflow->method('getRouteId')->willReturn('1-1');
        $parentWorkflow->method('getRouteConfig')->willReturn(['type' => 'literal']);
        $parentWorkflow->method('getRouteTitle')->willReturn(null);
        $parentWorkflow->method('getRoutePages')->willReturn([]);
        $parentWorkflow->method('getResourceId')->willReturn('parent');
        $parentWorkflow->method('getPageChangeFrequency')->willReturn(null);
        $parentWorkflow->method('getPriority')->willReturn('0.5');
        $parentWorkflow->method('getLandingPage')->willReturn(false);

        $this->pluginManager->method('build')->willReturnOnConsecutiveCalls(
            $parentWorkflow,
            $childWorkflow
        );
        $this->repository->method('getWorkflowResources')->willReturn([$parentResource]);

        $captured = null;
        $this->cache->method('hasItem')->willReturn(false);
        $this->cache->method('setItem')->willReturnCallback(
            function (string $key, array $data) use (&$captured): bool {
                $captured = $data;
                return true;
            }
        );

        $strategy = $this->makeStrategy();
        $strategy->getNavigationConfig();

        $this->assertNotNull($captured);
        $this->assertSame(['type' => 'literal'], $captured['route']['1-1']);
        $this->assertSame(['type' => 'segment'], $captured['route']['1-2']);
        $this->assertCount(1, $captured['navigation']);
        $this->assertCount(1, $captured['navigation'][0]['pages']);
        $this->assertSame('1-2', $captured['navigation'][0]['pages'][0]['route']);
    }

    public function testProcessSkipsRoutesWithEmptyConfig(): void
    {
        $resource = new ResourceStub();
        $resource->setPrimaryKeys(['resource_id' => 1]);

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('rid');
        $workflow->method('getRouteConfig')->willReturn([]); // empty -> skipped
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $this->pluginManager->method('build')->willReturn($workflow);
        $this->repository->method('getWorkflowResources')->willReturn([$resource]);

        $captured = null;
        $this->cache->method('hasItem')->willReturn(false);
        $this->cache->method('setItem')->willReturnCallback(
            function (string $key, array $data) use (&$captured): bool {
                $captured = $data;
                return true;
            }
        );

        $strategy = $this->makeStrategy();
        $strategy->getNavigationConfig();

        $this->assertSame([], $captured['route']);
    }

    public function testGetResourceWorkFlowDefaultsToPageWhenWorkflowMissing(): void
    {
        $resource = new ResourceStub();
        unset($resource->workflow); // simulate missing property

        $workflow = $this->createMock(AbstractWorkflow::class);
        $workflow->method('getResource')->willReturn($resource);
        $workflow->method('getRouteId')->willReturn('rid');
        $workflow->method('getRouteConfig')->willReturn([]);
        $workflow->method('getRouteTitle')->willReturn(null);
        $workflow->method('getRoutePages')->willReturn([]);
        $workflow->method('getResourceId')->willReturn('rid');
        $workflow->method('getPageChangeFrequency')->willReturn(null);
        $workflow->method('getPriority')->willReturn('0.5');
        $workflow->method('getLandingPage')->willReturn(false);

        $this->pluginManager->expects($this->once())
            ->method('build')
            ->with('page')
            ->willReturn($workflow);
        $this->repository->method('getWorkflowResources')->willReturn([$resource]);
        $this->cache->method('hasItem')->willReturn(false);
        $this->cache->method('setItem')->willReturn(true);

        $strategy = $this->makeStrategy();
        $strategy->getNavigationConfig();
    }
}
