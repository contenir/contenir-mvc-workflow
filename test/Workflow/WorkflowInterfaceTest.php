<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Workflow\PageWorkflow;
use Contenir\Mvc\Workflow\Workflow\WorkflowInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_map;
use function sort;

class WorkflowInterfaceTest extends TestCase
{
    public function testPageWorkflowImplementsInterface(): void
    {
        $this->assertInstanceOf(WorkflowInterface::class, new PageWorkflow());
    }

    public function testInterfaceDefinesRequiredMethods(): void
    {
        $reflection = new ReflectionClass(WorkflowInterface::class);
        $methods    = array_map(fn ($m) => $m->getName(), $reflection->getMethods());
        sort($methods);
        $this->assertSame(
            ['getNavigationConfig', 'getRouteConfig', 'getRouteId', 'getRoutePath'],
            $methods
        );
    }
}
