<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Integration\Navigation;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Module;
use Contenir\Mvc\Workflow\Navigation\AbstractWorkflowNavigationFactory;
use Contenir\Mvc\Workflow\Navigation\WorkflowNavigationFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Navigation\SiteNavigationFactory;
use ContenirTest\Mvc\Workflow\Trait\WorkflowServicesTrait;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Page\Mvc;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_map;
use function iterator_to_array;

#[CoversClass(AbstractWorkflowNavigationFactory::class)]
#[Group('integration')]
final class WorkflowNavigationFactoryTest extends TestCase
{
    use WorkflowServicesTrait;

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>, string}>
     */
    public static function misconfigurationProvider(): array
    {
        return [
            'no workflow manager'        => [
                ['workflow_manager' => []],
                [],
                'No workflow manager configuration found',
            ],
            'no strategy'                => [
                ['workflow_manager' => ['strategy' => null, 'navigation' => ['name' => 'site']]],
                [],
                'No workflow strategy configuration found',
            ],
            'strategy of the wrong type' => [
                ['workflow_manager' => ['strategy' => ['type' => 'not-a-strategy']]],
                ['not-a-strategy' => new stdClass()],
                'Workflow strategy "not-a-strategy" must implement '
                    . 'Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface, stdClass given',
            ],
            'no navigation name'         => [
                ['workflow_manager' => ['strategy' => ['type' => 'workflow_strategy', 'repository' => 'site-tree']]],
                [],
                'No workflow navigation configuration found',
            ],
        ];
    }

    #[Test]
    public function buildsTheVisiblePagesWithTheirUrls(): void
    {
        $services  = $this->servicesWithApplication();
        $bootstrap = new MvcEvent();
        $bootstrap->setApplication($this->createConfiguredStub(Application::class, ['getServiceManager' => $services]));
        (new Module())->onBootstrap($bootstrap);

        $factory    = new WorkflowNavigationFactory();
        $navigation = $factory($services, Navigation::class);

        $about = $navigation->findOneBy('route', '1-1');
        static::assertInstanceOf(Mvc::class, $about);

        static::assertSame(
            ['site', ['/about', '/news'], ['/about/team']],
            [
                $factory->getName(),
                array_map(
                    static fn(Mvc $page): string => $page->getHref(),
                    iterator_to_array($navigation, preserve_keys: false),
                ),
                array_map(
                    static fn(Mvc $page): string => $page->getHref(),
                    iterator_to_array($about, preserve_keys: false),
                ),
            ],
        );
    }

    #[Test]
    public function nameCanBeChanged(): void
    {
        $factory = new WorkflowNavigationFactory();

        $factory->setName('footer');

        static::assertSame('footer', $factory->getName());
    }

    #[Test]
    public function nameDefaultsToCms(): void
    {
        static::assertSame('cms', (new WorkflowNavigationFactory())->getName());
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $services
     */
    #[Test]
    #[DataProvider('misconfigurationProvider')]
    public function rejectsMisconfiguration(array $config, array $services, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new WorkflowNavigationFactory())($this->servicesWithApplication($config, $services), Navigation::class);
    }

    #[Test]
    public function subclassesMayChangeTheDefaultName(): void
    {
        static::assertSame('site-default', (new SiteNavigationFactory())->getName());
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $services
     */
    private function servicesWithApplication(array $config = [], array $services = []): ServiceManager
    {
        $router = new TreeRouteStack();
        $event  = new MvcEvent();
        $event->setRequest(new Request());
        $event->setRouter($router);

        $application = $this->createStub(Application::class);
        $application->method('getMvcEvent')->willReturn($event);

        return self::workflowServices($config, [
            'router'      => $router,
            'Application' => $application,
            ...$services,
        ]);
    }
}
