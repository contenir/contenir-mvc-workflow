<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow;

use Contenir\Mvc\Workflow\Container\WorkflowConfig;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteStackInterface;
use Psr\Container\ContainerExceptionInterface;

use function get_debug_type;
use function sprintf;

/**
 * laminas-mvc module: registers the services and, on bootstrap, adds the
 * strategy's routes to the router.
 *
 * @api
 */
final class Module
{
    /**
     * @return array{
     *     service_manager: array{aliases: array<string, class-string>, factories: array<class-string, class-string>},
     *     workflow_manager: array{strategy: array<never, never>},
     *     workflow: array<never, never>,
     * }
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
     * @throws InvalidArgumentException When the strategy is not configured.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; narrowed here.
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $serviceManager = $event->getApplication()->getServiceManager();
        $config         = WorkflowConfig::from($serviceManager);

        if ([] === $config->strategy()) {
            throw new InvalidArgumentException('No workflow strategy configuration found');
        }

        $strategyType = $config->strategyType();
        if (null === $strategyType) {
            throw new InvalidArgumentException('No workflow strategy type configured');
        }

        $strategy = $serviceManager->get($strategyType);
        if (! $strategy instanceof ResourceStrategyInterface) {
            throw new InvalidArgumentException(sprintf(
                'Workflow strategy "%s" must implement %s, %s given',
                $strategyType,
                ResourceStrategyInterface::class,
                get_debug_type($strategy),
            ));
        }

        $router = $serviceManager->get('router');
        if (! $router instanceof RouteStackInterface) {
            throw new InvalidArgumentException(sprintf(
                'The "router" service must implement %s, %s given',
                RouteStackInterface::class,
                get_debug_type($router),
            ));
        }

        $router->addRoutes($strategy->getRouteConfig());
    }
}
