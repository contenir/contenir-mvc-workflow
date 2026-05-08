<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\PluginManagerFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class PluginManagerFactoryTest extends TestCase
{
    public function testFactoryProducesPluginManagerWithConfig(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            'workflow_manager' => [
                'factories' => [],
                'aliases'   => [],
            ],
        ]);

        $factory       = new PluginManagerFactory();
        $pluginManager = $factory($container, PluginManager::class);

        $this->assertInstanceOf(PluginManager::class, $pluginManager);
    }

    public function testFactoryFallsBackToEmptyArrayWhenWorkflowManagerFalsy(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            'workflow_manager' => [],
        ]);

        $factory       = new PluginManagerFactory();
        $pluginManager = $factory($container, PluginManager::class);

        $this->assertInstanceOf(PluginManager::class, $pluginManager);
    }

    public function testFactoryAcceptsOptionalArguments(): void
    {
        $container = new ServiceManager();
        $container->setService('config', [
            'workflow_manager' => [],
        ]);

        $factory       = new PluginManagerFactory();
        $pluginManager = $factory($container, PluginManager::class, ['ignored' => true]);

        $this->assertInstanceOf(PluginManager::class, $pluginManager);
    }
}
