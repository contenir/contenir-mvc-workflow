<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Strategy;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Strategy\AbstractResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyFactory;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use ContenirTest\Mvc\Workflow\TestAsset\Container\InMemoryContainer;
use ContenirTest\Mvc\Workflow\TestAsset\Strategy\SiteResourceStrategy;
use Laminas\Cache\Storage\StorageInterface;
use Laminas\ServiceManager\PluginManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ResourceStrategyFactory::class)]
#[Group('unit')]
final class ResourceStrategyFactoryTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function noCacheProvider(): array
    {
        return [
            'no options key' => [['repository' => 'my-repo']],
            'empty options'  => [['repository' => 'my-repo', 'options' => []]],
            'empty cache'    => [['repository' => 'my-repo', 'options' => ['cache' => '']]],
            'null cache'     => [['repository' => 'my-repo', 'options' => ['cache' => null]]],
        ];
    }

    /**
     * @return array<string, array{class-string, class-string}>
     */
    public static function requestedClassProvider(): array
    {
        return [
            'interface alias'   => [ResourceStrategyInterface::class, ResourceStrategy::class],
            'abstract base'     => [AbstractResourceStrategy::class, ResourceStrategy::class],
            'application class' => [SiteResourceStrategy::class, SiteResourceStrategy::class],
        ];
    }

    /**
     * @param class-string $requested
     * @param class-string $expected
     */
    #[Test]
    #[DataProvider('requestedClassProvider')]
    public function buildsTheRequestedConcreteStrategy(string $requested, string $expected): void
    {
        $strategy = (new ResourceStrategyFactory())($this->container(['repository' => 'my-repo']), $requested);

        static::assertSame($expected, $strategy::class);
    }

    #[Test]
    public function cacheInstanceIsUsedAsIs(): void
    {
        $cache = $this->createStub(StorageInterface::class);

        $strategy = (new ResourceStrategyFactory())(
            $this->container(['repository' => 'my-repo', 'options' => ['cache' => $cache]]),
            ResourceStrategy::class,
        );

        static::assertInstanceOf(ResourceStrategy::class, $strategy);
        static::assertSame($cache, $strategy->getCache());
    }

    /**
     * @param array<string, mixed> $strategyConfig
     */
    #[Test]
    #[DataProvider('noCacheProvider')]
    public function cacheIsOptional(array $strategyConfig): void
    {
        $strategy = (new ResourceStrategyFactory())($this->container($strategyConfig), ResourceStrategy::class);

        static::assertInstanceOf(ResourceStrategy::class, $strategy);
        static::assertNull($strategy->getCache());
    }

    #[Test]
    public function repositoryMustBeAResourceAdapter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Service "my-repo" must be an instance of Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface, stdClass given',
        );

        (new ResourceStrategyFactory())(
            $this->container(['repository' => 'my-repo'], ['my-repo' => new stdClass()]),
            ResourceStrategy::class,
        );
    }

    #[Test]
    public function repositoryMustBeConfigured(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No repository found in workflow strategy configuration');

        (new ResourceStrategyFactory())($this->container(['options' => []]), ResourceStrategy::class);
    }

    #[Test]
    public function wiresTheRepositoryCacheAndPluginManager(): void
    {
        $repository    = $this->createStub(ResourceAdapterInterface::class);
        $cache         = $this->createStub(StorageInterface::class);
        $pluginManager = $this->createStub(PluginManagerInterface::class);

        $strategy = (new ResourceStrategyFactory())(
            $this->container(
                ['repository' => 'my-repo', 'options' => ['cache' => 'my-cache', 'cache_key' => 'XKey']],
                ['my-repo' => $repository, 'my-cache' => $cache, 'workflow_plugin_manager' => $pluginManager],
            ),
            ResourceStrategy::class,
        );

        static::assertInstanceOf(ResourceStrategy::class, $strategy);
        static::assertSame(
            [$repository, $cache, $pluginManager],
            [$strategy->getRepository(), $strategy->getCache(), $strategy->getPluginManager()],
        );
    }

    /**
     * @param array<string, mixed> $strategyConfig
     * @param array<string, mixed> $services
     */
    private function container(array $strategyConfig, array $services = []): InMemoryContainer
    {
        return new InMemoryContainer([
            'config'                  => ['workflow_manager' => ['strategy' => $strategyConfig]],
            'my-repo'                 => $this->createStub(ResourceAdapterInterface::class),
            'workflow_plugin_manager' => $this->createStub(PluginManagerInterface::class),
            ...$services,
        ]);
    }
}
