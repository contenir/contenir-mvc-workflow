<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Strategy;

use Contenir\Mvc\Workflow\Strategy\AbstractResourceStrategy;

/**
 * An application strategy that declares its own option defaults and leaves
 * "use_parent_as_landing_page" out of them.
 */
final class OptionlessResourceStrategy extends AbstractResourceStrategy
{
    /** @var array<array-key, mixed> */
    protected array $options = ['cache_key' => 'ResourceStrategyCache'];
}
