<?php

namespace Contenir\Mvc\Workflow;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

class PluginManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config = $container->get('config');

        $pluginManager = new PluginManager($container, $config['workflow_manager'] ?: []);

        return $pluginManager;
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
