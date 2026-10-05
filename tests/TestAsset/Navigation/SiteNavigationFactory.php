<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Navigation;

use Contenir\Mvc\Workflow\Navigation\AbstractWorkflowNavigationFactory;

/**
 * An application's navigation factory with its own default name.
 */
final class SiteNavigationFactory extends AbstractWorkflowNavigationFactory
{
    protected string $name = 'site-default';
}
