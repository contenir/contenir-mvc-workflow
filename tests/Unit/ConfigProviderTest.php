<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit;

use Contenir\Mvc\Workflow\ConfigProvider;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\PluginManagerFactory;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyFactory;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigProvider::class)]
#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function providesTheServicesAndAnEmptyStrategy(): void
    {
        static::assertSame(
            [
                'dependencies'     => [
                    'aliases'   => [
                        'workflow_plugin_manager' => PluginManager::class,
                        'workflow_strategy'       => ResourceStrategyInterface::class,
                    ],
                    'factories' => [
                        PluginManager::class             => PluginManagerFactory::class,
                        ResourceStrategyInterface::class => ResourceStrategyFactory::class,
                    ],
                ],
                'workflow_manager' => ['strategy' => []],
            ],
            (new ConfigProvider())(),
        );
    }
}
