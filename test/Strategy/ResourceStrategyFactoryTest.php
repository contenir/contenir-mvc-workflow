<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyFactory;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\TestCase;

class ResourceStrategyFactoryTest extends TestCase
{
    private function buildContainer(array $config): ServiceManager
    {
        $container = new ServiceManager();
        $container->setService('config', $config);
        $container->setService(
            'workflow_plugin_manager',
            $this->createMock(PluginManager::class)
        );
        return $container;
    }

    public function testProducesStrategyWithRepositoryAndCacheResolved(): void
    {
        $repository = $this->createMock(ResourceAdapterInterface::class);
        $cache      = $this->createMock(StorageInterface::class);

        $container = $this->buildContainer([
            'workflow_manager' => [
                'strategy' => [
                    'repository' => 'my-repo',
                    'options'    => [
                        'cache'     => 'my-cache',
                        'cache_key' => 'XKey',
                    ],
                ],
            ],
        ]);
        $container->setService('my-repo', $repository);
        $container->setService('my-cache', $cache);

        $factory  = new ResourceStrategyFactory();
        $strategy = $factory($container, ResourceStrategy::class);

        $this->assertInstanceOf(ResourceStrategy::class, $strategy);
        $this->assertSame($repository, $strategy->getRepository());
        $this->assertSame($cache, $strategy->getCache());
    }

    public function testWorksWhenCacheNotConfigured(): void
    {
        $repository = $this->createMock(ResourceAdapterInterface::class);

        $container = $this->buildContainer([
            'workflow_manager' => [
                'strategy' => [
                    'repository' => 'my-repo',
                    'options'    => [],
                ],
            ],
        ]);
        $container->setService('my-repo', $repository);

        $factory  = new ResourceStrategyFactory();
        $strategy = $factory($container, ResourceStrategy::class);

        $this->assertInstanceOf(ResourceStrategy::class, $strategy);
        $this->assertSame($repository, $strategy->getRepository());
    }

    public function testThrowsWhenRepositoryMissing(): void
    {
        $container = $this->buildContainer([
            'workflow_manager' => [
                'strategy' => [
                    'options' => [],
                ],
            ],
        ]);

        $factory = new ResourceStrategyFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No repository found in workflow strategy configuration');

        $factory($container, ResourceStrategy::class);
    }
}
