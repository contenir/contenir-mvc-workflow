<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ResourceStrategyFactory
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): ResourceStrategyInterface {
        $options = $container->get('config')['workflow_manager']['strategy'];

        $repositoryClass = $options['repository'] ?? null;
        if (empty($repositoryClass)) {
            throw new InvalidArgumentException('No repository found in workflow strategy configuration');
        }
        $repository = $container->get($repositoryClass);

        $cacheObject = $options['options']['cache'] ?? null;
        if ($cacheObject) {
            $options['options']['cache'] = $container->get($cacheObject);
        }

        $workflowPluginManager = $container->get('workflow_plugin_manager');

        return new $requestedName(
            $workflowPluginManager,
            $repository,
            $options['options']
        );
    }
}
