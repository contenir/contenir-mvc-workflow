<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Workflow;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Container\InMemoryContainer;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\ResourceFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\BareWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\IndexPageWorkflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkflowFactory::class)]
#[Group('unit')]
final class WorkflowFactoryTest extends TestCase
{
    #[Test]
    public function createsTheRequestedWorkflowWithTheWorkflowConfig(): void
    {
        $container = new InMemoryContainer(['config' => ['workflow' => ['page' => ['title' => 'Pages']]]]);

        $workflow = (new WorkflowFactory())($container, IndexPageWorkflow::class);
        $workflow->setResource(ResourceFactory::page());

        static::assertSame([IndexPageWorkflow::class, 'Pages'], [
            $workflow::class,
            $workflow->getNavigationConfig()['label'],
        ]);
    }

    #[Test]
    public function rejectsClassesThatDoNotExtendAbstractWorkflow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'WorkflowFactory can only create Contenir\Mvc\Workflow\Workflow\AbstractWorkflow subclasses',
        );

        (new WorkflowFactory())(new InMemoryContainer(), BareWorkflow::class);
    }

    #[Test]
    public function workflowConfigIsOptional(): void
    {
        $workflow = (new WorkflowFactory())(new InMemoryContainer(), IndexPageWorkflow::class);
        $workflow->setResource(ResourceFactory::page());

        static::assertNull($workflow->getNavigationConfig()['label']);
    }
}
