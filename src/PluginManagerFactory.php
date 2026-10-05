<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

use Contenir\Mvc\Workflow\Container\WorkflowConfig;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the workflow plugin manager from the "workflow_manager" configuration.
 *
 * @api
 */
final class PluginManagerFactory
{
    /**
     * @param array<array-key, mixed>|null $options
     *
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:unused-parameter Laminas factory signature; the plugin manager needs neither.
     * @mago-expect analysis:less-specific-nested-argument-type laminas-servicemanager validates its own configuration.
     */
    public function __invoke(ContainerInterface $container, string $name, ?array $options = null): PluginManager
    {
        return new PluginManager($container, WorkflowConfig::from($container)->workflowManager());
    }
}
