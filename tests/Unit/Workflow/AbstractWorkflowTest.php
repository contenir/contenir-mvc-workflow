<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Workflow;

use ArrayIterator;
use Contenir\Mvc\Workflow\Exception\RuntimeException;
use Contenir\Mvc\Workflow\Workflow\AbstractWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\IndexController;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\MagicResource;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\ResourceFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\ConfiguredWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\DescribedWorkflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractWorkflow::class)]
#[Group('unit')]
final class AbstractWorkflowTest extends TestCase
{
    /**
     * @return array<string, array{string|null, string}>
     *
     * @mago-expect lint:no-literal-namespace-string Controller class names are the data under test.
     */
    public static function resourceIdProvider(): array
    {
        return [
            'application controller' => ['Application\Controller\IndexController', 'controller:application.index'],
            'nested namespace'       => ['My\Module\Controller\AdminController', 'controller:my\module.admin'],
            'no controller'          => [null, 'controller:'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function routeIdProvider(): array
    {
        return [
            'without a path' => ['', '7-99'],
            'with a path'    => ['sub', '7-99/sub'],
        ];
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function routeTitleProvider(): array
    {
        return [
            'not configured' => [null],
            'configured'     => ['Section'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function slugProvider(): array
    {
        return [
            'nested slug'    => ['parent/child', '/parent/child'],
            'empty segments' => ['//double//empty', '/double/empty'],
            'empty slug'     => ['', '/'],
        ];
    }

    /**
     * @return array<string, array{array<array-key, mixed>}>
     */
    public static function unusableWorkflowConfigProvider(): array
    {
        return [
            'no entry for the workflow' => [['article' => ['title' => 'Article']]],
            'entry is not an array'     => [['page' => 'Page']],
            'title is not a string'     => [['page' => ['title' => 42]]],
        ];
    }

    #[Test]
    public function changeFrequencyAndPriorityDefaultToNoneAndHalf(): void
    {
        $workflow = new ConfiguredWorkflow();

        static::assertSame([null, '0.5'], [$workflow->getPageChangeFrequency(), $workflow->getPriority()]);
    }

    #[Test]
    public function configIsNotAppliedBeforeAResourceNamesTheWorkflow(): void
    {
        $workflow = new ConfiguredWorkflow(workflowConfig: ['' => ['title' => 'Unnamed']]);

        static::assertNull($workflow->getNavigationConfig()['label']);
    }

    #[Test]
    public function configMayBeTraversable(): void
    {
        $workflow = new ConfiguredWorkflow();
        $workflow->setConfig(new ArrayIterator(['page' => ['title' => 'Generated Title']]));
        $workflow->setResource(ResourceFactory::page());

        static::assertSame('Generated Title', $workflow->getNavigationConfig()['label']);
    }

    #[Test]
    public function configSetAfterTheResourceIsAppliedImmediately(): void
    {
        $workflow = new ConfiguredWorkflow();
        $workflow->setResource(ResourceFactory::page(overrides: ['workflow' => 'article']));
        $workflow->setConfig(['article' => ['title' => 'Article Title']]);

        static::assertSame('Article Title', $workflow->getNavigationConfig()['label']);
    }

    #[Test]
    public function configuredRouteIdIgnoresThePath(): void
    {
        $workflow = new ConfiguredWorkflow(routeId: 'preset/route');

        static::assertSame(['preset/route', 'preset/route'], [$workflow->getRouteId(), $workflow->getRouteId('x')]);
    }

    #[Test]
    public function configuredRoutePathIsUsedAsIs(): void
    {
        static::assertSame('/explicit/path', (new ConfiguredWorkflow(routePath: '/explicit/path'))->getRoutePath());
    }

    #[Test]
    public function descriptionComesFromTheWorkflowConfig(): void
    {
        $workflow = new DescribedWorkflow(['page' => ['title' => 'Pages', 'description' => 'Site pages']]);
        $workflow->setResource(ResourceFactory::page());

        static::assertSame('Site pages', $workflow->getNavigationConfig()['description']);
    }

    #[Test]
    public function landingPageCanBeSwitchedOn(): void
    {
        $workflow = new ConfiguredWorkflow();
        $workflow->setLandingPage(flag: true);

        static::assertTrue($workflow->getLandingPage());
    }

    #[Test]
    public function landingPageIsOffByDefault(): void
    {
        static::assertFalse((new ConfiguredWorkflow())->getLandingPage());
    }

    #[Test]
    public function navigationConfigDescribesTheWorkflowPage(): void
    {
        $workflow = new ConfiguredWorkflow(
            workflowConfig: ['page' => ['title' => 'My Title']],
            changeFrequency: 'weekly',
            priority: '0.9',
        );
        $workflow->setResource(new MagicResource(['resource_type_id' => 7, 'resource_id' => 99]));

        static::assertSame(
            [
                'label'      => 'My Title',
                'route'      => '7-99',
                'changefreq' => 'weekly',
                'priority'   => '0.9',
                'visible'    => true,
                'pages'      => [],
            ],
            $workflow->getNavigationConfig(),
        );
    }

    #[Test]
    #[DataProvider('resourceIdProvider')]
    public function resourceIdIsDerivedFromTheController(?string $controller, string $expected): void
    {
        static::assertSame($expected, (new ConfiguredWorkflow(controller: $controller))->getResourceId());
    }

    #[Test]
    public function resourceIsNullBeforeOneIsSet(): void
    {
        static::assertNull((new ConfiguredWorkflow())->getResource());
    }

    #[Test]
    public function routeControllerIsTheConfiguredController(): void
    {
        static::assertSame(IndexController::class, (new ConfiguredWorkflow())->getRouteController());
    }

    #[Test]
    public function routeControllerMustBeConfigured(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workflow ' . ConfiguredWorkflow::class . ' has no controller configured');

        (new ConfiguredWorkflow(controller: null))->getRouteController();
    }

    #[Test]
    #[DataProvider('routeIdProvider')]
    public function routeIdIsBuiltFromTheResourceTypeAndId(string $path, string $expected): void
    {
        $workflow = new ConfiguredWorkflow();
        $workflow->setResource(new MagicResource(['resource_type_id' => 7, 'resource_id' => 99]));

        static::assertSame($expected, $workflow->getRouteId($path));
    }

    #[Test]
    public function routeIdWithoutAResourceFallsBackToEmptyIds(): void
    {
        static::assertSame('-0', (new ConfiguredWorkflow())->getRouteId());
    }

    #[Test]
    public function routePagesAreTheConfiguredPages(): void
    {
        $pages = ['sub' => [['title' => 'Sub']]];

        static::assertSame($pages, (new ConfiguredWorkflow(pages: $pages))->getRoutePages());
    }

    #[Test]
    #[DataProvider('slugProvider')]
    public function routePathIsBuiltFromTheResourceSlug(string $slug, string $expected): void
    {
        $workflow = new ConfiguredWorkflow();
        $workflow->setResource(new MagicResource(slug: $slug));

        static::assertSame($expected, $workflow->getRoutePath());
    }

    #[Test]
    public function routePathNeedsAResource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Workflow ' . ConfiguredWorkflow::class . ' has no resource set');

        (new ConfiguredWorkflow())->getRoutePath();
    }

    #[Test]
    #[DataProvider('routeTitleProvider')]
    public function routeTitleIsTheConfiguredTitle(?string $title): void
    {
        static::assertSame($title, (new ConfiguredWorkflow(routeTitle: $title))->getRouteTitle());
    }

    #[Test]
    public function setResourceExposesTheResourceAndItsWorkflowId(): void
    {
        $resource = ResourceFactory::page(overrides: ['workflow' => 'article']);
        $workflow = new ConfiguredWorkflow();
        $workflow->setResource($resource);

        static::assertSame([$resource, 'article'], [$workflow->getResource(), $workflow->getWorkflowId()]);
    }

    /**
     * @param array<array-key, mixed> $config
     */
    #[Test]
    #[DataProvider('unusableWorkflowConfigProvider')]
    public function unusableWorkflowConfigLeavesTheTitleUnset(array $config): void
    {
        $workflow = new ConfiguredWorkflow(workflowConfig: $config);
        $workflow->setResource(ResourceFactory::page());

        static::assertNull($workflow->getNavigationConfig()['label']);
    }

    #[Test]
    public function workflowIdDefaultsToPageWhenTheResourceHasNone(): void
    {
        $workflow = new ConfiguredWorkflow();
        $workflow->setResource(new MagicResource());

        static::assertSame('page', $workflow->getWorkflowId());
    }

    #[Test]
    public function workflowIdIsUnavailableBeforeAResourceIsSet(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Workflow ' . ConfiguredWorkflow::class . ' has no workflow id until a resource is set',
        );

        (new ConfiguredWorkflow())->getWorkflowId();
    }

    #[Test]
    public function workflowTitleComesFromTheResourcesWorkflowEntry(): void
    {
        $workflow = new ConfiguredWorkflow(workflowConfig: [
            'page'    => ['title' => 'Page Title', 'description' => 'About pages'],
            'article' => ['title' => 'Article Title'],
        ]);
        $workflow->setResource(ResourceFactory::page());

        static::assertSame('Page Title', $workflow->getNavigationConfig()['label']);
    }

    #[Test]
    public function workflowTitleIsUnresolvedUntilAResourceIsSet(): void
    {
        $workflow = new ConfiguredWorkflow(
            workflowConfig: ['page' => ['title' => 'Page Title']],
            routeId: 'r',
        );

        static::assertNull($workflow->getNavigationConfig()['label']);
    }
}
