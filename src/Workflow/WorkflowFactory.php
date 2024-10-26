<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Workflow;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class WorkflowFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param string             $requestedName
     * @param null|array         $options
     * @throws ContainerExceptionInterface If any other error occurs.
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): object
    {
        $config = $container->get('config')['workflow'];

        $workflow = new $requestedName();
        $workflow->setConfig($config);

        return $workflow;
    }
}
