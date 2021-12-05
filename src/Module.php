<?php

namespace Contenir\Mvc\Workflow;

use Contenir\Mvc\Workflow\PluginManager;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Laminas\Mvc\MvcEvent;

class Module
{
    /**
     * Retrieve default laminas-paginator config for laminas-mvc context.
     *
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();

        return [
            'service_manager' => $provider->getDependencyConfig(),
            'workflow_manager' => $provider->getWorkflowManagerConfig(),
            'workflow' => []
        ];
    }

    public function onBootstrap(MvcEvent $event)
    {
        $application    = $event->getApplication();
        $serviceManager = $application->getServiceManager();

        $config = $serviceManager->get('config')['workflow_manager']['strategy'] ?? null;
        if (empty($config)) {
            throw new InvalidArgumentException('No workflow strategy configuration found');
        }

        $strategy = $serviceManager->get($config['type']);

        $navigationConfig = $strategy->getNavigationConfig();
        $routeConfig = $strategy->getRouteConfig();

        $router = $serviceManager->get('router');
        $router->addRoutes($routeConfig);
    }
}