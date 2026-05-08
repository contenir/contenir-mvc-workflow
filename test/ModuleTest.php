<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow;

use Contenir\Mvc\Workflow\ConfigProvider;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Module;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteStackInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testGetConfigMatchesConfigProvider(): void
    {
        $module   = new Module();
        $config   = $module->getConfig();
        $provider = new ConfigProvider();

        $this->assertArrayHasKey('service_manager', $config);
        $this->assertArrayHasKey('workflow_manager', $config);
        $this->assertArrayHasKey('workflow', $config);

        $this->assertSame($provider->getDependencyConfig(), $config['service_manager']);
        $this->assertSame($provider->getWorkflowManagerConfig(), $config['workflow_manager']);
        $this->assertSame([], $config['workflow']);
    }

    public function testOnBootstrapAddsRoutesFromStrategy(): void
    {
        $strategy = $this->createMock(ResourceStrategyInterface::class);
        $strategy->expects($this->never())
            ->method('getNavigationConfig');
        $strategy->expects($this->once())
            ->method('getRouteConfig')
            ->willReturn(['the-route' => ['type' => 'literal']]);

        $router = $this->createMock(RouteStackInterface::class);
        $router->expects($this->once())
            ->method('addRoutes')
            ->with(['the-route' => ['type' => 'literal']]);

        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', [
            'workflow_manager' => [
                'strategy' => [
                    'type' => 'workflow_strategy',
                ],
            ],
        ]);
        $serviceManager->setService('workflow_strategy', $strategy);
        $serviceManager->setService('router', $router);

        $application = $this->createMock(Application::class);
        $application->method('getServiceManager')->willReturn($serviceManager);

        $event = new MvcEvent();
        $event->setApplication($application);

        (new Module())->onBootstrap($event);
    }

    public function testOnBootstrapThrowsWhenStrategyConfigMissing(): void
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', ['workflow_manager' => ['strategy' => null]]);

        $application = $this->createMock(Application::class);
        $application->method('getServiceManager')->willReturn($serviceManager);

        $event = new MvcEvent();
        $event->setApplication($application);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No workflow strategy configuration found');

        (new Module())->onBootstrap($event);
    }

    public function testOnBootstrapThrowsWhenStrategyConfigEmpty(): void
    {
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', ['workflow_manager' => ['strategy' => []]]);

        $application = $this->createMock(Application::class);
        $application->method('getServiceManager')->willReturn($serviceManager);

        $event = new MvcEvent();
        $event->setApplication($application);

        $this->expectException(InvalidArgumentException::class);
        (new Module())->onBootstrap($event);
    }
}
