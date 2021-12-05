<?php

namespace Contenir\Mvc\Workflow;

use Interop\Container\ContainerInterface;
use Laminas\ConfigAggregator\ArrayProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\ConfigAggregator\LaminasConfigProvider;
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
