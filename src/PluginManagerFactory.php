<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

use interop\container\containerinterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PluginManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(containerinterface $container, string $name, ?array $options = null): PluginManager
    {
        $config = $container->get('config');

        return new PluginManager($container, $config['workflow_manager'] ?: []);
    }
}
