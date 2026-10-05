<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Contenir\Mvc\Workflow\Container\WorkflowConfig;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_a;
use function sprintf;

/**
 * Creates any AbstractWorkflow subclass and gives it the "workflow" configuration.
 *
 * @api
 */
final class WorkflowFactory implements FactoryInterface
{
    /**
     * @param string $requestedName
     * @param array<array-key, mixed>|null $options
     *
     * @throws InvalidArgumentException When $requestedName is not an AbstractWorkflow subclass.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:unsafe-instantiation Workflows are documented as argument-less constructible.
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): AbstractWorkflow
    {
        if (! is_a($requestedName, AbstractWorkflow::class, allow_string: true)) {
            throw new InvalidArgumentException(sprintf(
                'WorkflowFactory can only create %s subclasses',
                AbstractWorkflow::class,
            ));
        }

        $workflow = new $requestedName();
        $workflow->setConfig(WorkflowConfig::from($container)->workflows());

        return $workflow;
    }
}
