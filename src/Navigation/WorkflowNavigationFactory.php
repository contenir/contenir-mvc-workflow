<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Navigation;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Interop\Container\ContainerInterface;
use Laminas\Navigation\Navigation;
use Laminas\Navigation\Service\AbstractNavigationFactory;

class WorkflowNavigationFactory extends AbstractNavigationFactory
{
    protected $cache;
    protected $name = 'cms';

    /**
     * Create and return a new Navigation instance (v3).
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     * @return Navigation
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
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

        $pages = $this->preparePages($container, $navigationConfig);

        return new Navigation($pages);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}
