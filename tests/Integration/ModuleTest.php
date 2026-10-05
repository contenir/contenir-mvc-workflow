<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Integration;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Module;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\IndexController;
use ContenirTest\Mvc\Workflow\TestAsset\Controller\NewsController;
use ContenirTest\Mvc\Workflow\Trait\WorkflowServicesTrait;
use Laminas\Http\Request;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversMethod(Module::class, 'onBootstrap')]
#[Group('integration')]
final class ModuleTest extends TestCase
{
    use WorkflowServicesTrait;

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>, string}>
     */
    public static function misconfigurationProvider(): array
    {
        return [
            'no strategy'                => [
                ['workflow_manager' => ['strategy' => []]],
                [],
                'No workflow strategy configuration found',
            ],
            'no strategy type'           => [
                ['workflow_manager' => ['strategy' => ['repository' => 'site-tree']]],
                [],
                'No workflow strategy type configured',
            ],
            'strategy of the wrong type' => [
                ['workflow_manager' => ['strategy' => ['type' => 'not-a-strategy']]],
                ['not-a-strategy' => new stdClass()],
                'Workflow strategy "not-a-strategy" must implement '
                    . 'Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface, stdClass given',
            ],
            'router of the wrong type'   => [
                [],
                ['router' => new stdClass()],
                'The "router" service must implement Laminas\Router\RouteStackInterface, stdClass given',
            ],
        ];
    }

    /**
     * @return array<string, array{string, string, array<string, mixed>}>
     */
    public static function routedUrlProvider(): array
    {
        return [
            'page'         => [
                '/about',
                '1-1',
                [
                    'controller'  => IndexController::class,
                    'action'      => 'index',
                    'resource_id' => ['resource_id' => 1],
                ],
            ],
            'child page'   => [
                '/about/team',
                '1-2',
                [
                    'controller'  => IndexController::class,
                    'action'      => 'index',
                    'resource_id' => ['resource_id' => 2],
                ],
            ],
            'article list' => [
                '/news',
                '1-3',
                [
                    'controller'  => NewsController::class,
                    'action'      => 'index',
                    'resource_id' => ['resource_id' => 3],
                ],
            ],
            'article'      => [
                '/news/hello',
                '1-3/post',
                [
                    'controller'  => NewsController::class,
                    'action'      => 'view',
                    'resource_id' => ['resource_id' => 3],
                    'slug'        => 'hello',
                ],
            ],
            'hidden page'  => [
                '/hidden',
                '1-4',
                [
                    'controller'  => IndexController::class,
                    'action'      => 'index',
                    'resource_id' => ['resource_id' => 4],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $services
     */
    #[Test]
    #[DataProvider('misconfigurationProvider')]
    public function bootstrapRejectsMisconfiguration(array $config, array $services, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        (new Module())->onBootstrap($this->event(self::workflowServices($config, $services)));
    }

    /**
     * @param array<string, mixed> $params
     */
    #[Test]
    #[DataProvider('routedUrlProvider')]
    public function bootstrapRoutesEveryResource(string $url, string $routeName, array $params): void
    {
        $services = self::workflowServices();
        (new Module())->onBootstrap($this->event($services));

        $router = $services->get('router');
        static::assertInstanceOf(TreeRouteStack::class, $router);

        $request = new Request();
        $request->setUri("https://example.com{$url}");
        $match = $router->match($request);

        static::assertInstanceOf(RouteMatch::class, $match);
        static::assertSame([$routeName, $params], [$match->getMatchedRouteName(), $match->getParams()]);
    }

    private function event(ServiceManager $services): MvcEvent
    {
        $application = $this->createStub(ApplicationInterface::class);
        $application->method('getServiceManager')->willReturn($services);

        $event = new MvcEvent();
        $event->setApplication($application);

        return $event;
    }
}
