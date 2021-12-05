<?php

namespace Contenir\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ResourceStrategyFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $config = $container->get('config')['workflow_manager']['strategy'];

        $repositoryClass = $config['options']['repository'] ?? null;
        if (empty($repositoryClass)) {
            throw new InvalidArgumentException('No repository found in workflow strategy configuration');
        }

        $cacheObject = $config['options']['cache'] ?? null;
        if ($cacheObject) {
            $cacheObject = $container->get($cacheObject);
        }

        $repository = $container->get($repositoryClass);
        $workflowPluginManager = $container->get('workflow_plugin_manager');

        $strategy = new $requestedName($workflowPluginManager, $repository, $cacheObject);

        return $strategy;
    }

    /**
     * {@inheritDoc}
     *
     * @return WriterPluginManager
     */
    public function createService(ServiceLocatorInterface $container, $name = null, $requestedName = null)
    {
        return $this($container, $requestedName ?: PluginManager::class, $this->creationOptions);
    }
}
