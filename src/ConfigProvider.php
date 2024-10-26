<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

class ConfigProvider
{
    /**
     * Retrieve default laminas-paginator configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies'     => $this->getDependencyConfig(),
            'workflow_manager' => $this->getWorkflowManagerConfig(),
        ];
    }

    /**
     * Retrieve dependency configuration for laminas-paginator.
     */
    public function getDependencyConfig(): array
    {
        return [
            'aliases'   => [
                'workflow_plugin_manager' => PluginManager::class,
                'workflow_strategy'       => Strategy\ResourceStrategyInterface::class,
            ],
            'factories' => [
                PluginManager::class                      => PluginManagerFactory::class,
                Strategy\ResourceStrategyInterface::class => Strategy\ResourceStrategyFactory::class,
            ],
        ];
    }

    /**
     * Provide default route plugin manager configuration.
     */
    public function getWorkflowManagerConfig(): array
    {
        return [
            'strategy' => [],
        ];
    }
}
