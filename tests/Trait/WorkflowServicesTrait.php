<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Trait;

use Contenir\Mvc\Workflow\Module;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyFactory;
use Contenir\Mvc\Workflow\Workflow\WorkflowFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\ResourceFactory;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\IndexPageWorkflow;
use ContenirTest\Mvc\Workflow\TestAsset\Workflow\NewsWorkflow;
use Laminas\Router\Http\TreeRouteStack;
use Laminas\ServiceManager\ServiceManager;

/**
 * Builds a fresh ServiceManager wired the way an application configures
 * this package: the module's services, a ResourceStrategy over a small
 * site tree, and the page and news workflows.
 */
trait WorkflowServicesTrait
{
    private static function siteTree(): ResourceAdapterInterface
    {
        return new class implements ResourceAdapterInterface {
            /**
             * @return iterable<ResourceInterface>
             */
            public function getWorkflowResources(): iterable
            {
                return [
                    ResourceFactory::page(
                        id: 1,
                        slug: 'about',
                        overrides: [
                            'children' => [ResourceFactory::page(
                                id: 2,
                                slug: 'about/team',
                            )],
                        ],
                    ),
                    ResourceFactory::page(
                        id: 3,
                        slug: 'news',
                        overrides: ['workflow' => 'news', 'title' => 'News'],
                    ),
                    ResourceFactory::page(
                        id: 4,
                        slug: 'hidden',
                        overrides: ['visible' => false],
                    ),
                ];
            }
        };
    }

    /**
     * @param array<string, mixed> $configOverrides Replaces top-level config keys.
     * @param array<string, mixed> $services Extra or replacement services.
     */
    private static function workflowServices(array $configOverrides = [], array $services = []): ServiceManager
    {
        $module = (new Module())->getConfig();

        return new ServiceManager([
            ...$module['service_manager'],
            'factories' => [
                ...$module['service_manager']['factories'],
                ResourceStrategy::class => ResourceStrategyFactory::class,
            ],
            'services'  => [
                'config'    => [
                    'workflow_manager' => [
                        'aliases'    => ['page' => IndexPageWorkflow::class, 'news' => NewsWorkflow::class],
                        'factories'  => [
                            IndexPageWorkflow::class => WorkflowFactory::class,
                            NewsWorkflow::class      => WorkflowFactory::class,
                        ],
                        'strategy'   => ['type' => ResourceStrategy::class, 'repository' => 'site-tree'],
                        'navigation' => ['name' => 'site'],
                    ],
                    'workflow'         => ['page' => ['title' => 'Pages'], 'news' => ['title' => 'News']],
                    ...$configOverrides,
                ],
                'router'    => new TreeRouteStack(),
                'site-tree' => self::siteTree(),
                ...$services,
            ],
        ]);
    }
}
