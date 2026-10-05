<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Strategy;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use Contenir\Mvc\Workflow\Strategy\AbstractResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\MagicResource;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\MetadataResource;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\ResourceFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Strategy\OptionlessResourceStrategy;
use ContenirTest\Mvc\Workflow\TestAsset\Strategy\TracingResourceStrategy;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\BareWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\ConfiguredWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\RoutelessWorkflow;
use DateTimeImmutable;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_column;
use function array_key_exists;
use function array_keys;
use function array_map;

#[CoversClass(AbstractResourceStrategy::class)]
#[Group('unit')]
final class ResourceStrategyTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function cacheKeyProvider(): array
    {
        return [
            'configured' => ['custom-key', 'custom-key'],
            'not scalar' => [['key'], 'ResourceStrategyCache'],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function corruptCacheProvider(): array
    {
        return [
            'not an array'        => ['corrupt'],
            'sections not arrays' => [['route' => 'x', 'navigation' => 'y']],
        ];
    }

    /**
     * @return array<string, array{DateTimeImmutable|null, string|null}>
     */
    public static function lastModifiedProvider(): array
    {
        return [
            'modified date' => [new DateTimeImmutable('2024-01-02 03:04:05'), '2024-01-02 03:04:05'],
            'no date'       => [null, null],
        ];
    }

    /**
     * @return array<string, array{bool, array<array-key, mixed>}>
     */
    public static function noParentLandingPageProvider(): array
    {
        return [
            'option off'  => [false, [ResourceFactory::page(id: 2)]],
            'no children' => [true, []],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function overriddenPageProvider(): array
    {
        return [
            'no pages key'     => [['label' => 'External', 'uri' => 'https://example.com']],
            'pages not a list' => [['label' => 'External', 'pages' => 'none']],
        ];
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function parentLandingPageProvider(): array
    {
        return [
            'route title'    => ['Overview', 'Overview'],
            'resource title' => [null, 'Page 1'],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function subPageLabelProvider(): array
    {
        return [
            'route page title' => [['title' => 'Long', 'title_short' => 'Short'], 'Route Page'],
            'short title'      => [['title' => 'Long', 'title_short' => 'Short'], 'Short'],
            'resource title'   => [['title' => 'Long'], 'Long'],
        ];
    }

    /**
     * @param array<array-key, mixed> $resources
     */
    private static function repository(array $resources): ResourceAdapterInterface
    {
        return new class($resources) implements ResourceAdapterInterface {
            /**
             * @param array<array-key, mixed> $resources
             */
            public function __construct(
                private readonly array $resources,
            ) {}

            /**
             * @return iterable<ResourceInterface>
             */
            public function getWorkflowResources(): iterable
            {
                /** @var iterable<ResourceInterface> */
                return $this->resources;
            }
        };
    }

    #[Test]
    public function cacheCanBeSetDirectly(): void
    {
        $cache    = $this->createStub(StorageInterface::class);
        $strategy = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]));

        $strategy->setCache($cache);

        static::assertSame($cache, $strategy->getCache());
    }

    #[Test]
    public function cacheHitIsUsedWithoutReadingResources(): void
    {
        $cache = $this->createStub(StorageInterface::class);
        $cache->method('hasItem')->willReturn(true);
        $cache->method('getItem')->willReturn(['route' => ['cached' => []], 'navigation' => [['label' => 'Cached']]]);
        $repository = $this->createMock(ResourceAdapterInterface::class);
        $repository->expects($this->never())->method('getWorkflowResources');
        $strategy = new ResourceStrategy($this->plugins(), $repository, ['cache' => $cache]);

        static::assertSame(
            [['cached' => []], [['label' => 'Cached']]],
            [$strategy->getRouteConfig(), $strategy->getNavigationConfig()],
        );
    }

    #[Test]
    #[DataProvider('cacheKeyProvider')]
    public function cacheKeyOptionNamesTheCacheItem(mixed $option, string $expected): void
    {
        $cache = $this->createMock(StorageInterface::class);
        $cache->expects($this->once())->method('hasItem')->with($expected)->willReturn(true);
        $cache->method('getItem')->willReturn(['route' => [], 'navigation' => []]);
        $strategy = new ResourceStrategy($this->plugins(), self::repository([]), [
            'cache'     => $cache,
            'cache_key' => $option,
        ]);

        static::assertSame([], $strategy->getRouteConfig());
    }

    #[Test]
    public function cacheMissBuildsAndStoresTheTree(): void
    {
        $cache = $this->createMock(StorageInterface::class);
        $cache->method('hasItem')->willReturn(false);
        $cache->expects($this->once())
            ->method('setItem')
            ->with(
                'ResourceStrategyCache',
                static::callback(
                    static fn(array $resources): bool => array_keys($resources) === ['route', 'navigation'],
                ),
            )
            ->willReturn(true);
        $strategy = new ResourceStrategy($this->plugins(), self::repository([ResourceFactory::page()]), [
            'cache' => $cache,
        ]);

        static::assertSame(['1-1'], array_keys($strategy->getRouteConfig()));
    }

    #[Test]
    public function cacheOptionIsPassedToSetCache(): void
    {
        $cache    = $this->createStub(StorageInterface::class);
        $strategy = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]), [
            'cache' => $cache,
        ]);

        static::assertSame($cache, $strategy->getCache());
    }

    /**
     * Applications override getNavigationPage(), e.g. to turn redirects into URI pages.
     *
     * @param array<string, mixed> $overriddenPage
     */
    #[Test]
    #[DataProvider('overriddenPageProvider')]
    public function childrenAreNestedUnderOverriddenPages(array $overriddenPage): void
    {
        $child = ResourceFactory::page(
            id: 2,
            slug: 'about/team',
        );
        $parent = ResourceFactory::page(
            id: 1,
            slug: 'about',
            overrides: ['children' => [$child]],
        );
        $strategy = new class($overriddenPage, $this->plugins(), self::repository([$parent])) extends
            AbstractResourceStrategy {
            /**
             * @param array<string, mixed> $overriddenPage
             */
            public function __construct(
                private readonly array $overriddenPage,
                PluginManagerInterface $pluginManager,
                ResourceAdapterInterface $repository,
            ) {
                parent::__construct($pluginManager, $repository);
            }

            /**
             * @return array<string, mixed>
             */
            public function getNavigationPage(WorkflowInterface $workflow): array
            {
                return $workflow->getRouteId() === '1-1' ? $this->overriddenPage : parent::getNavigationPage($workflow);
            }
        };

        static::assertSame(['1-2'], array_column($strategy->getNavigationConfig()[0]['pages'] ?? [], 'route'));
    }

    #[Test]
    public function childrenAreNestedUnderTheirParentsPage(): void
    {
        $child = ResourceFactory::page(
            id: 2,
            slug: 'about/team',
        );
        $parent = ResourceFactory::page(
            id: 1,
            slug: 'about',
            overrides: ['children' => [$child]],
        );
        $strategy = new ResourceStrategy($this->plugins(), self::repository([$parent]));

        $navigation = $strategy->getNavigationConfig();

        static::assertSame(
            [['1-1', '1-2'], '1-2'],
            [array_keys($strategy->getRouteConfig()), $navigation[0]['pages'][0]['route'] ?? null],
        );
    }

    #[Test]
    public function childrenFollowTheParentsOwnLandingPage(): void
    {
        $parent = ResourceFactory::page(
            id: 1,
            slug: 'about',
            overrides: ['children' => [ResourceFactory::page(
                id: 2,
                slug: 'about/team',
            )]],
        );
        $strategy = new ResourceStrategy($this->plugins(), self::repository([$parent]), [
            'use_parent_as_landing_page' => true,
        ]);

        static::assertSame(['1-1', '1-2'], array_column($strategy->getNavigationConfig()[0]['pages'] ?? [], 'route'));
    }

    #[Test]
    public function collaboratorsCanBeReplaced(): void
    {
        $pluginManager = $this->createStub(PluginManagerInterface::class);
        $repository    = self::repository([]);
        $strategy      = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]));

        $strategy->setPluginManager($pluginManager);
        $strategy->setRepository($repository);

        static::assertSame(
            [$pluginManager, $repository],
            [$strategy->getPluginManager(), $strategy->getRepository()],
        );
    }

    #[Test]
    public function configSetAfterBuildingIsKept(): void
    {
        $strategy = new ResourceStrategy($this->plugins(), self::repository([]));
        $strategy->getRouteConfig();

        $strategy->setRouteConfig(['manual' => []]);
        $strategy->setNavigationConfig([['label' => 'Manual']]);

        static::assertSame(
            [['manual' => []], [['label' => 'Manual']]],
            [$strategy->getRouteConfig(), $strategy->getNavigationConfig()],
        );
    }

    #[Test]
    #[DataProvider('corruptCacheProvider')]
    public function corruptCacheItemsReadAsEmptyOrAreRebuilt(mixed $item): void
    {
        $cache = $this->createStub(StorageInterface::class);
        $cache->method('hasItem')->willReturn(true);
        $cache->method('getItem')->willReturn($item);
        $strategy = new ResourceStrategy($this->plugins(), self::repository([]), ['cache' => $cache]);

        static::assertSame([[], []], [$strategy->getRouteConfig(), $strategy->getNavigationConfig()]);
    }

    #[Test]
    public function eachResourceGetsTheWorkflowItNames(): void
    {
        $built         = [];
        $pluginManager = $this->createStub(PluginManagerInterface::class);
        $pluginManager->method('build')
            ->willReturnCallback(static function (string $name) use (&$built): ConfiguredWorkflow {
                $built[] = $name;

                return new ConfiguredWorkflow();
            });
        $strategy = new ResourceStrategy($pluginManager, self::repository([
            ResourceFactory::page(
                id: 1,
                overrides: ['workflow' => 'article'],
            ),
            new MagicResource(['visible' => false]),
        ]));

        $strategy->getRouteConfig();

        static::assertSame(['article', 'page'], $built);
    }

    #[Test]
    public function exposesItsCollaborators(): void
    {
        $pluginManager = $this->createStub(PluginManagerInterface::class);
        $repository    = self::repository([]);
        $strategy      = new ResourceStrategy($pluginManager, $repository);

        static::assertSame(
            [$pluginManager, $repository, null],
            [$strategy->getPluginManager(), $strategy->getRepository(), $strategy->getCache()],
        );
    }

    #[Test]
    public function hiddenResourcesAreRoutedButNotInTheNavigation(): void
    {
        $strategy = new ResourceStrategy($this->plugins(), self::repository([
            ResourceFactory::page(
                id: 1,
                overrides: ['visible' => false],
            ),
        ]));

        static::assertSame([['1-1'], []], [
            array_keys($strategy->getRouteConfig()),
            $strategy->getNavigationConfig(),
        ]);
    }

    #[Test]
    public function hiddenResourcesDoNotHideTheirSiblings(): void
    {
        $strategy = new ResourceStrategy($this->plugins(), self::repository([
            ResourceFactory::page(
                id: 1,
                overrides: ['visible' => false],
            ),
            ResourceFactory::page(id: 2),
            ResourceFactory::page(id: 3),
        ]));

        static::assertSame(['1-2', '1-3'], array_column($strategy->getNavigationConfig(), 'route'));
    }

    #[Test]
    public function landingPageCollectsTheRoutePages(): void
    {
        $workflow = new ConfiguredWorkflow(
            routeId: 'parent',
            routeTitle: 'Overview',
            pages: ['a' => [['title' => 'A']], 'b' => [['title' => 'B']]],
        );
        $workflow->setLandingPage(flag: true);

        $landing = $this->navigationPage($workflow, ResourceFactory::page())['pages'][0];

        static::assertSame(
            ['Overview', false, ['parent/a', 'parent/b']],
            [$landing['label'], $landing['useRouteMatch'], array_column($landing['pages'], 'route')],
        );
    }

    #[Test]
    public function landingPageNeedsMoreThanOneRoutePageSet(): void
    {
        $workflow = new ConfiguredWorkflow(
            routeId: 'parent',
            pages: ['sub' => [['title' => 'Sub']]],
        );
        $workflow->setLandingPage(flag: true);

        $page = $this->navigationPage($workflow, ResourceFactory::page());

        static::assertSame(['parent/sub'], array_column($page['pages'], 'route'));
    }

    #[Test]
    public function landingPageWithEmptyRoutePageSetsHasNoPages(): void
    {
        $workflow = new ConfiguredWorkflow(
            routeId: 'parent',
            routeTitle: 'Overview',
            pages: ['a' => [], 'b' => 'x'],
        );
        $workflow->setLandingPage(flag: true);

        $landing = $this->navigationPage($workflow, ResourceFactory::page())['pages'][0];

        static::assertSame([false, []], [$landing['useRouteMatch'], $landing['pages']]);
    }

    #[Test]
    #[DataProvider('lastModifiedProvider')]
    public function lastModifiedComesFromPageMetadata(?DateTimeImmutable $modified, ?string $expected): void
    {
        $page = $this->navigationPage(new ConfiguredWorkflow(), new MetadataResource(['title' => 'T'], $modified));

        static::assertSame($expected, $page['lastmod']);
    }

    #[Test]
    public function navigationPageDescribesTheResource(): void
    {
        $page = $this->navigationPage(
            new ConfiguredWorkflow(
                changeFrequency: 'daily',
                priority: '1.0',
            ),
            ResourceFactory::page(
                id: 1,
                overrides: ['title' => 'Long Title', 'title_short' => 'Short'],
            ),
        );

        static::assertSame(
            [
                'label'      => 'Short',
                'route'      => '1-1',
                'lastmod'    => null,
                'changefreq' => 'daily',
                'priority'   => '1.0',
                'visible'    => true,
                'resource'   => 'controller:contenirtest\\mvc\\workflow\\testasset.index',
                'pages'      => [],
            ],
            $page,
        );
    }

    #[Test]
    public function navigationPageNeedsAnAbstractWorkflow(): void
    {
        $strategy = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]));

        $this->expectException(InvalidArgumentException::class);

        $strategy->getNavigationPage(new BareWorkflow());
    }

    #[Test]
    public function navigationPageNeedsAResource(): void
    {
        $strategy = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workflow ' . ConfiguredWorkflow::class . ' has no resource set');

        $strategy->getNavigationPage(new ConfiguredWorkflow());
    }

    /**
     * @param array<array-key, mixed> $children
     */
    #[Test]
    #[DataProvider('noParentLandingPageProvider')]
    public function parentIsNotItsOwnLandingPageOtherwise(bool $option, array $children): void
    {
        $strategy = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]), [
            'use_parent_as_landing_page' => $option,
        ]);
        $workflow = new ConfiguredWorkflow();
        $workflow->setResource(ResourceFactory::page(overrides: ['children' => $children]));

        static::assertSame([], $strategy->getNavigationPage($workflow)['pages']);
    }

    #[Test]
    public function parentIsNotItsOwnLandingPageWhenASubclassOmitsTheOption(): void
    {
        $strategy = new OptionlessResourceStrategy(
            $this->createStub(PluginManagerInterface::class),
            self::repository([]),
        );
        $workflow = new ConfiguredWorkflow();
        $workflow->setResource(ResourceFactory::page(overrides: ['children' => [ResourceFactory::page(id: 2)]]));

        static::assertSame([], $strategy->getNavigationPage($workflow)['pages']);
    }

    #[Test]
    #[DataProvider('parentLandingPageProvider')]
    public function parentWithChildrenBecomesItsOwnLandingPageWhenEnabled(?string $routeTitle, string $label): void
    {
        $strategy = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]), [
            'use_parent_as_landing_page' => true,
        ]);
        $workflow = new ConfiguredWorkflow(routeTitle: $routeTitle);
        $workflow->setResource(ResourceFactory::page(overrides: ['children' => [ResourceFactory::page(id: 2)]]));

        $landing = $strategy->getNavigationPage($workflow)['pages'][0];

        static::assertSame([$label, true, []], [$landing['label'], $landing['useRouteMatch'], $landing['pages']]);
    }

    #[Test]
    public function pluginsMustExtendAbstractWorkflow(): void
    {
        $strategy = new ResourceStrategy(
            $this->plugins(['page' => static fn(): BareWorkflow => new BareWorkflow()]),
            self::repository([ResourceFactory::page()]),
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Workflows must extend Contenir\Mvc\Workflow\Workflow\AbstractWorkflow, ' . BareWorkflow::class . ' given',
        );

        $strategy->getRouteConfig();
    }

    #[Test]
    public function routesAreKeyedByRouteId(): void
    {
        $strategy = new ResourceStrategy($this->plugins(), self::repository([
            ResourceFactory::page(
                id: 1,
                slug: 'about',
            ),
            ResourceFactory::page(
                id: 2,
                slug: 'contact',
            ),
        ]));

        static::assertSame(
            [
                '1-1' => ['type' => 'literal', 'options' => ['route' => '/about']],
                '1-2' => ['type' => 'literal', 'options' => ['route' => '/contact']],
            ],
            $strategy->getRouteConfig(),
        );
    }

    #[Test]
    public function setOptionsReturnsTheStrategy(): void
    {
        $strategy = new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]));

        static::assertSame($strategy, $strategy->setOptions(['use_parent_as_landing_page' => true]));
    }

    #[Test]
    public function severalRoutePageSetsWithoutALandingPageAreListedUnderThePage(): void
    {
        $page = $this->navigationPage(
            new ConfiguredWorkflow(
                routeId: 'parent',
                pages: ['a' => [['title' => 'A']], 'b' => [['title' => 'B']]],
            ),
            ResourceFactory::page(),
        );

        static::assertSame(['parent/a', 'parent/b'], array_column($page['pages'], 'route'));
    }

    #[Test]
    public function singleRoutePageSetIsListedUnderThePage(): void
    {
        $page = $this->navigationPage(
            new ConfiguredWorkflow(
                routeId: 'parent',
                pages: ['sub' => [['title' => 'Sub Page', 'params' => ['p' => 1]]]],
            ),
            ResourceFactory::page(),
        );

        static::assertSame(
            [
                false,
                [[
                    'label'   => 'Sub Page',
                    'visible' => true,
                    'route'   => 'parent/sub',
                    'params'  => ['p' => 1],
                    'pages'   => [],
                ]],
            ],
            [$page['useRouteMatch'] ?? null, $page['pages']],
        );
    }

    #[Test]
    public function subclassesCanDecorateEveryProtectedHook(): void
    {
        $strategy = new TracingResourceStrategy(
            $this->plugins([
                'page' => static fn(): ConfiguredWorkflow => new ConfiguredWorkflow(pages: [
                    'sub' => [['title' => 'Sub']],
                ]),
            ]),
            self::repository([ResourceFactory::page()]),
        );

        $strategy->getNavigationConfig();

        static::assertSame(['build', 'process', 'workflow 1-1', 'sub-page 1-1/sub'], $strategy->trace);
    }

    /**
     * @param array<string, mixed> $columns
     */
    #[Test]
    #[DataProvider('subPageLabelProvider')]
    public function subPageLabelFallsBackToTheResourceTitles(array $columns, string $label): void
    {
        $routePage = 'Route Page' === $label ? ['title' => 'Route Page'] : [];
        $page      = $this->navigationPage(
            new ConfiguredWorkflow(pages: ['sub' => [$routePage]]),
            ResourceFactory::page(overrides: $columns),
        );

        static::assertSame($label, $page['pages'][0]['label']);
    }

    #[Test]
    public function subPagesNestAndRepeatThemselvesUnderALandingTitle(): void
    {
        $page = $this->navigationPage(
            new ConfiguredWorkflow(
                routeId: 'parent',
                pages: [
                    'a' => [
                        'not a page',
                        [
                            'title'        => 'A',
                            'landingTitle' => 'All of A',
                            'pages'        => ['b' => [['title' => 'B']], 'c' => 'not a list'],
                        ],
                    ],
                ],
            ),
            ResourceFactory::page(),
        );

        $sub = $page['pages'][0];

        static::assertSame(
            [
                'A',
                [
                    ['All of A', 'parent/a'],
                    ['B',        'parent/a/b'],
                ],
            ],
            [
                $sub['label'],
                array_map(static fn(array $p): array => [$p['label'], $p['route']], $sub['pages']),
            ],
        );
    }

    #[Test]
    public function unknownOptionsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Method setUnknownOption() does not exist');

        new ResourceStrategy($this->createStub(PluginManagerInterface::class), self::repository([]), [
            'unknown_option' => 'value',
        ]);
    }

    #[Test]
    public function withoutACacheTheTreeIsBuiltOncePerInstance(): void
    {
        $repository = $this->createMock(ResourceAdapterInterface::class);
        $repository->expects($this->once())
            ->method('getWorkflowResources')
            ->willReturn([ResourceFactory::page(
                id: 1,
                slug: 'about',
            )]);
        $strategy = new ResourceStrategy($this->plugins(), $repository);

        $strategy->getRouteConfig();
        $navigation = $strategy->getNavigationConfig();

        static::assertSame('1-1', $navigation[0]['route'] ?? null);
    }

    #[Test]
    public function workflowsWithoutARouteAddNone(): void
    {
        $strategy = new ResourceStrategy(
            $this->plugins(['page' => static fn(): RoutelessWorkflow => new RoutelessWorkflow()]),
            self::repository([ResourceFactory::page()]),
        );

        static::assertSame([], $strategy->getRouteConfig());
    }

    /**
     * @return array<string, mixed>
     */
    private function navigationPage(ConfiguredWorkflow $workflow, ResourceInterface $resource): array
    {
        $workflow->setResource($resource);

        return (new ResourceStrategy(
            $this->createStub(PluginManagerInterface::class),
            self::repository([]),
        ))->getNavigationPage(
            $workflow,
        );
    }

    /**
     * A plugin manager building a new ConfiguredWorkflow per resource, or
     * the workflow a $factories entry creates for that name.
     *
     * @param array<string, callable(): WorkflowInterface|object> $factories
     */
    private function plugins(array $factories = []): PluginManagerInterface
    {
        $pluginManager = $this->createStub(PluginManagerInterface::class);
        $pluginManager->method('build')
            ->willReturnCallback(static fn(string $name): object => array_key_exists($name, $factories)
                ? $factories[$name]()
                : new ConfiguredWorkflow());

        return $pluginManager;
    }
}
