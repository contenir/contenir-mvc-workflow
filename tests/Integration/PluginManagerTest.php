<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Integration;

use Contenir\Mvc\Workflow\PluginManager;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\NewsWorkflow;
use ContenirTest\Mvc\Workflow\Trait\WorkflowServicesTrait;
use Laminas\ServiceManager\Exception\InvalidServiceException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(PluginManager::class)]
#[Group('integration')]
final class PluginManagerTest extends TestCase
{
    use WorkflowServicesTrait;

    #[Test]
    public function buildsWorkflowsByResourceWorkflowName(): void
    {
        $pluginManager = self::workflowServices()->get('workflow_plugin_manager');
        static::assertInstanceOf(PluginManager::class, $pluginManager);

        static::assertInstanceOf(NewsWorkflow::class, $pluginManager->build('news'));
    }

    #[Test]
    public function rejectsPluginsThatAreNotWorkflows(): void
    {
        $pluginManager = self::workflowServices()->get('workflow_plugin_manager');
        static::assertInstanceOf(PluginManager::class, $pluginManager);

        $this->expectException(InvalidServiceException::class);

        $pluginManager->validate(new stdClass());
    }
}
