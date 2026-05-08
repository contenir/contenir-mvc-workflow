<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Workflow\PageWorkflow;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;
use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class PluginManagerTest extends TestCase
{
    public function testExtendsAbstractPluginManager(): void
    {
        $pluginManager = new PluginManager(new ServiceManager());
        $this->assertInstanceOf(AbstractPluginManager::class, $pluginManager);
    }

    public function testInstanceOfTargetIsWorkflowInterface(): void
    {
        $reflection = new ReflectionClass(PluginManager::class);
        $property   = $reflection->getProperty('instanceOf');
        $property->setAccessible(true);

        $pluginManager = new PluginManager(new ServiceManager());

        $this->assertSame(WorkflowInterface::class, $property->getValue($pluginManager));
    }

    public function testValidatesWorkflowInstances(): void
    {
        $serviceManager = new ServiceManager();
        $pluginManager  = new PluginManager(
            $serviceManager,
            [
                'factories' => [
                    PageWorkflow::class => WorkflowFactory::class,
                ],
            ]
        );
        $serviceManager->setService('config', ['workflow' => []]);

        $instance = $pluginManager->build(PageWorkflow::class);
        $this->assertInstanceOf(WorkflowInterface::class, $instance);
    }

    public function testValidateRejectsNonWorkflowInstance(): void
    {
        $pluginManager = new PluginManager(new ServiceManager());

        $this->expectException(InvalidServiceException::class);
        $pluginManager->validate(new stdClass());
    }

    public function testValidateAcceptsWorkflowInstance(): void
    {
        $pluginManager  = new PluginManager(new ServiceManager());
        $serviceManager = new ServiceManager();
        $serviceManager->setService('config', ['workflow' => []]);
        $factory  = new WorkflowFactory();
        $workflow = $factory($serviceManager, PageWorkflow::class);

        $pluginManager->validate($workflow);
        $this->assertInstanceOf(WorkflowInterface::class, $workflow);
    }
}
