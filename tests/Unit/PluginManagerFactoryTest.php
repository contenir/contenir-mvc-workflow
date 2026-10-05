<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\PluginManagerFactory;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Container\InMemoryContainer;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\IndexPageWorkflow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PluginManagerFactory::class)]
#[Group('unit')]
final class PluginManagerFactoryTest extends TestCase
{
    #[Test]
    public function configuresThePluginManagerFromWorkflowManager(): void
    {
        $container = new InMemoryContainer([
            'config' => [
                'workflow_manager' => [
                    'factories' => [IndexPageWorkflow::class => WorkflowFactory::class],
                    'strategy'  => [],
                ],
            ],
        ]);

        $pluginManager = (new PluginManagerFactory())($container, PluginManager::class);

        static::assertTrue($pluginManager->has(IndexPageWorkflow::class));
    }

    #[Test]
    public function missingConfigurationGivesAnEmptyPluginManager(): void
    {
        $pluginManager = (new PluginManagerFactory())(new InMemoryContainer(), PluginManager::class);

        static::assertFalse($pluginManager->has(IndexPageWorkflow::class));
    }
}
