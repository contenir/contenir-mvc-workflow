<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Navigation;

use Contenir\Mvc\Workflow\Container\WorkflowConfig;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Service\AbstractNavigationFactory;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function get_debug_type;
use function sprintf;

/**
 * Base navigation factory, and the extension point for applications: builds a laminas-navigation container from the strategy's navigation
 * configuration, named by "workflow_manager.navigation.name".
 *
 * @api
 */
abstract class AbstractWorkflowNavigationFactory extends AbstractNavigationFactory
{
    protected string $name = 'cms';

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string $requestedName
     * @param array<array-key, mixed>|null $options
     *
     * @throws InvalidArgumentException When the workflow configuration is incomplete.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; narrowed here.
     * @mago-expect analysis:less-specific-nested-argument-type laminas-navigation validates each page specification.
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Navigation
    {
        $config = WorkflowConfig::from($container);
        if ([] === $config->workflowManager()) {
            throw new InvalidArgumentException('No workflow manager configuration found');
        }

        $strategyType = $config->strategyType();
        if (null === $strategyType) {
            throw new InvalidArgumentException('No workflow strategy configuration found');
        }

        $strategy = $container->get($strategyType);
        if (! $strategy instanceof ResourceStrategyInterface) {
            throw new InvalidArgumentException(sprintf(
                'Workflow strategy "%s" must implement %s, %s given',
                $strategyType,
                ResourceStrategyInterface::class,
                get_debug_type($strategy),
            ));
        }

        $navigationName = $config->navigationName();
        if (null === $navigationName) {
            throw new InvalidArgumentException('No workflow navigation configuration found');
        }

        $this->setName($navigationName);

        return new Navigation($this->preparePages($container, $strategy->getNavigationConfig()));
    }
}
