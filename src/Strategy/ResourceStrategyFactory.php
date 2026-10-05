<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\Container\WorkflowConfig;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function in_array;
use function is_a;
use function is_string;
use function sprintf;

/**
 * Builds a ResourceStrategy (or the subclass requested) from
 * "workflow_manager.strategy": the "repository" service, and "options",
 * whose "cache" entry names a laminas-cache storage service.
 *
 * @api
 */
final class ResourceStrategyFactory
{
    /**
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T
     *
     * @throws InvalidArgumentException When the service is not a T.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; narrowed here.
     */
    private static function service(ContainerInterface $container, string $name, string $type): object
    {
        $service = $container->get($name);
        if (! $service instanceof $type) {
            throw new InvalidArgumentException(sprintf(
                'Service "%s" must be an instance of %s, %s given',
                $name,
                $type,
                get_debug_type($service),
            ));
        }

        return $service;
    }

    /**
     * @param array<array-key, mixed>|null $options
     *
     * @throws InvalidArgumentException When the repository is missing or a service has the wrong type.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:unsafe-instantiation Strategy subclasses keep ResourceStrategy's constructor.
     * @mago-expect analysis:ambiguous-instantiation-target The requested subclass, or ResourceStrategy for the interface alias.
     * @mago-expect analysis:unused-parameter Laminas factory signature; the strategy takes no build options.
     * @mago-expect analysis:mixed-assignment Strategy options are untyped configuration; the strategy validates them.
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null,
    ): ResourceStrategyInterface {
        $config = WorkflowConfig::from($container);

        $repositoryName = $config->strategyRepository();
        if (null === $repositoryName) {
            throw new InvalidArgumentException('No repository found in workflow strategy configuration');
        }

        $repository = self::service($container, $repositoryName, ResourceAdapterInterface::class);

        $strategyOptions = $config->strategyOptions();
        $cacheName       = $strategyOptions['cache'] ?? null;
        if (in_array($cacheName, [null, '', false], strict: true)) {
            unset($strategyOptions['cache']);
        }

        if (is_string($cacheName) && '' !== $cacheName) {
            $strategyOptions['cache'] = $container->get($cacheName);
        }

        $pluginManager = self::service($container, 'workflow_plugin_manager', PluginManager::class);

        $class = is_a($requestedName, ResourceStrategy::class, allow_string: true)
            ? $requestedName
            : ResourceStrategy::class;

        return new $class($pluginManager, $repository, $strategyOptions);
    }
}
