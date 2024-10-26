<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Navigation;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Service\AbstractNavigationFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class WorkflowNavigationFactory extends AbstractNavigationFactory
{
    protected string $name = 'cms';

    /**
     * Create and return a new Navigation instance
     *
     * @param string             $requestedName
     * @param null|array         $options
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): Navigation {
        $config = $container->get('config')['workflow_manager'] ?? [];
        if (empty($config)) {
            throw new InvalidArgumentException('No workflow manager configuration found');
        }

        $strategyConfig = $config['strategy'] ?? null;
        if (empty($strategyConfig)) {
            throw new InvalidArgumentException('No workflow strategy configuration found');
        }
        $strategy = $container->get($strategyConfig['type']);

        $navigationName = $config['navigation']['name'] ?? null;
        if (empty($navigationName)) {
            throw new InvalidArgumentException('No workflow navigation configuration found');
        }
        $this->setName($navigationName);

        $navigationConfig = $strategy->getNavigationConfig();

        return new Navigation($this->preparePages($container, $navigationConfig));
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
