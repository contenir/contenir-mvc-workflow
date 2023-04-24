<?php

namespace Contenir\Mvc\Workflow;

use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class WorkflowFactory implements FactoryInterface
{
    protected $workflowConfig;

    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        $config = $container->get('config')['workflow'];

        $workflow = new $requestedName();
        $workflow->setConfig($config);

        return $workflow;
    }
}
