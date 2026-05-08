<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow;

use Contenir\Mvc\Workflow\ConfigProvider;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\PluginManagerFactory;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyFactory;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvokeReturnsCompleteConfiguration(): void
    {
        $config = ($this->provider)();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('workflow_manager', $config);
        $this->assertSame($this->provider->getDependencyConfig(), $config['dependencies']);
        $this->assertSame($this->provider->getWorkflowManagerConfig(), $config['workflow_manager']);
    }

    public function testGetDependencyConfigDefinesAliases(): void
    {
        $config = $this->provider->getDependencyConfig();

        $this->assertArrayHasKey('aliases', $config);
        $this->assertSame(
            PluginManager::class,
            $config['aliases']['workflow_plugin_manager']
        );
        $this->assertSame(
            ResourceStrategyInterface::class,
            $config['aliases']['workflow_strategy']
        );
    }

    public function testGetDependencyConfigDefinesFactories(): void
    {
        $config = $this->provider->getDependencyConfig();

        $this->assertArrayHasKey('factories', $config);
        $this->assertSame(
            PluginManagerFactory::class,
            $config['factories'][PluginManager::class]
        );
        $this->assertSame(
            ResourceStrategyFactory::class,
            $config['factories'][ResourceStrategyInterface::class]
        );
    }

    public function testGetWorkflowManagerConfigContainsEmptyStrategy(): void
    {
        $this->assertSame(
            ['strategy' => []],
            $this->provider->getWorkflowManagerConfig()
        );
    }
}
