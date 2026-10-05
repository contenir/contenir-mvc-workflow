<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

/**
 * Mezzio/laminas-config-aggregator configuration: the workflow plugin manager
 * and the default resource strategy services.
 *
 * @api
 */
final class ConfigProvider
{
    /**
     * Service manager configuration.
     *
     * @return array{aliases: array<string, class-string>, factories: array<class-string, class-string>}
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
     * Default "workflow_manager" configuration: no strategy until the
     * application configures one.
     *
     * @return array{strategy: array<never, never>}
     */
    public function getWorkflowManagerConfig(): array
    {
        return [
            'strategy' => [],
        ];
    }

    /**
     * @return array{
     *     dependencies: array{aliases: array<string, class-string>, factories: array<class-string, class-string>},
     *     workflow_manager: array{strategy: array<never, never>},
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies'     => $this->getDependencyConfig(),
            'workflow_manager' => $this->getWorkflowManagerConfig(),
        ];
    }
}
