<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Workflow\PageWorkflow;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class WorkflowFactoryTest extends TestCase
{
    public function testFactoryProducesRequestedWorkflowInstance(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            'workflow' => ['some-key' => ['title' => 'Hello']],
        ]);

        $factory  = new WorkflowFactory();
        $workflow = $factory($container, PageWorkflow::class);

        $this->assertInstanceOf(PageWorkflow::class, $workflow);
    }

    public function testFactoryAppliesConfigToWorkflow(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            'workflow' => ['ignored-key' => ['title' => 'Hello']],
        ]);

        $factory  = new WorkflowFactory();
        $workflow = $factory($container, PageWorkflow::class);

        $reflection = new ReflectionClass($workflow);
        $prop       = $reflection->getProperty('workflowConfig');
        $prop->setAccessible(true);
        // Without a resource set the resourceId is null so the resolved
        // config is the default empty array.
        $this->assertSame([], $prop->getValue($workflow));
    }

    public function testFactoryAcceptsOptions(): void
    {
        $container = new ServiceManager();
        $container->setService('config', ['workflow' => []]);

        $factory  = new WorkflowFactory();
        $workflow = $factory($container, PageWorkflow::class, ['some' => 'option']);

        $this->assertInstanceOf(PageWorkflow::class, $workflow);
    }
}
