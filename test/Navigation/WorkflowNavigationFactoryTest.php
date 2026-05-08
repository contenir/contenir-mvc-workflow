<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Navigation;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Navigation\WorkflowNavigationFactory;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Navigation\Navigation;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkflowNavigationFactoryTest extends TestCase
{
    /** @return ResourceStrategy&MockObject */
    private function makeStrategyMock(): ResourceStrategy
    {
        return $this->getMockBuilder(ResourceStrategy::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getNavigationConfig', 'getRouteConfig'])
            ->getMock();
    }

    private function makeContainer(array $config, ?ResourceStrategy $strategy = null): ServiceManager
    {
        $container = new ServiceManager();
        $container->setService('config', $config);

        if ($strategy !== null) {
            $container->setService('workflow_strategy', $strategy);
        }

        $event = new MvcEvent();
        $event->setRequest(new HttpRequest());
        $event->setRouter(new TreeRouteStack());
        $application = $this->createMock(Application::class);
        $application->method('getMvcEvent')->willReturn($event);
        $container->setService('Application', $application);

        return $container;
    }

    public function testNameDefaultsToCms(): void
    {
        $factory = new WorkflowNavigationFactory();
        $this->assertSame('cms', $factory->getName());
    }

    public function testSetNameMutatesName(): void
    {
        $factory = new WorkflowNavigationFactory();
        $factory->setName('site');
        $this->assertSame('site', $factory->getName());
    }

    public function testFactoryProducesNavigationFromStrategy(): void
    {
        $strategy = $this->makeStrategyMock();
        $strategy
            ->method('getNavigationConfig')
            ->willReturn([
                ['label' => 'Home', 'route' => 'home'],
            ]);

        $container = $this->makeContainer([
            'workflow_manager' => [
                'strategy'   => ['type' => 'workflow_strategy'],
                'navigation' => ['name' => 'primary'],
            ],
        ], $strategy);

        $factory    = new WorkflowNavigationFactory();
        $navigation = $factory($container, Navigation::class);

        $this->assertInstanceOf(Navigation::class, $navigation);
        $this->assertSame('primary', $factory->getName());
        $this->assertCount(1, $navigation);
    }

    public function testFactoryThrowsWhenWorkflowManagerConfigEmpty(): void
    {
        $container = $this->makeContainer(['workflow_manager' => []]);

        $factory = new WorkflowNavigationFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No workflow manager configuration found');

        $factory($container, Navigation::class);
    }

    public function testFactoryThrowsWhenStrategyConfigMissing(): void
    {
        $container = $this->makeContainer([
            'workflow_manager' => [
                'strategy' => null,
            ],
        ]);

        $factory = new WorkflowNavigationFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No workflow strategy configuration found');

        $factory($container, Navigation::class);
    }

    public function testFactoryThrowsWhenNavigationNameMissing(): void
    {
        $strategy  = $this->makeStrategyMock();
        $container = $this->makeContainer([
            'workflow_manager' => [
                'strategy'   => ['type' => 'workflow_strategy'],
                'navigation' => [],
            ],
        ], $strategy);

        $factory = new WorkflowNavigationFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No workflow navigation configuration found');

        $factory($container, Navigation::class);
    }
}
