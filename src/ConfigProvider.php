<?php

namespace Contenir\Mvc\Workflow;

class ConfigProvider
{
    /**
     * Retrieve default laminas-paginator configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'workflow_manager' => $this->getWorkflowManagerConfig(),
        ];
    }

    /**
     * Retrieve dependency configuration for laminas-paginator.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'aliases' => [
                'workflow_plugin_manager' => PluginManager::class,
                'workflow_strategy' => Strategy\StrategyInterface::class
            ],
            'factories' => [
                PluginManager::class => PluginManagerFactory::class,
                Strategy\StrategyInterface::class => Strategy\StrategyFactory::class,
            ]
        ];
    }

    /**
     * Provide default route plugin manager configuration.
     *
     * @return array
     */
    public function getWorkflowManagerConfig()
    {
        return [
            'strategy' => []
        ];
    }
}