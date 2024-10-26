<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Laminas\Mvc\MvcEvent;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Module
{
    /**
     * Retrieve default laminas-paginator config for laminas-mvc context.
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return [
            'service_manager'  => $provider->getDependencyConfig(),
            'workflow_manager' => $provider->getWorkflowManagerConfig(),
            'workflow'         => [],
        ];
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $application    = $event->getApplication();
        $serviceManager = $application->getServiceManager();

        $config = $serviceManager->get('config')['workflow_manager']['strategy'] ?? null;
        if (empty($config)) {
            throw new InvalidArgumentException('No workflow strategy configuration found');
        }

        $strategy = $serviceManager->get($config['type']);
        $strategy->getNavigationConfig();

        $routeConfig = $strategy->getRouteConfig();

        $router = $serviceManager->get('router');
        $router->addRoutes($routeConfig);
    }
}
