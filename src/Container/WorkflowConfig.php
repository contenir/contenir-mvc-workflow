<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_string;

/**
 * Typed reader over the untyped "workflow_manager" and "workflow" configuration.
 *
 * @internal
 *
 * @mago-expect lint:too-many-methods One small accessor per configuration key.
 */
final readonly class WorkflowConfig
{
    /**
     * @param array<array-key, mixed> $config
     */
    private function __construct(
        private array $config,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; accessors validate it.
     */
    public static function from(ContainerInterface $container): self
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return new self(is_array($config) ? $config : []);
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @return non-empty-string|null
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; narrowed here.
     */
    private static function nonEmptyString(array $config, string $key): ?string
    {
        $value = $config[$key] ?? null;

        return is_string($value) && '' !== $value ? $value : null;
    }

    /**
     * @param array<array-key, mixed> $config
     *
     * @return array<array-key, mixed>
     *
     * @mago-expect analysis:mixed-assignment Configuration is untyped input; narrowed here.
     */
    private static function section(array $config, string $key): array
    {
        $section = $config[$key] ?? null;

        return is_array($section) ? $section : [];
    }

    /**
     * Navigation container name ("workflow_manager.navigation.name").
     */
    public function navigationName(): ?string
    {
        return self::nonEmptyString(self::section($this->workflowManager(), 'navigation'), 'name');
    }

    /**
     * The "workflow_manager.strategy" section.
     *
     * @return array<array-key, mixed>
     */
    public function strategy(): array
    {
        return self::section($this->workflowManager(), 'strategy');
    }

    /**
     * Strategy constructor options ("workflow_manager.strategy.options").
     *
     * @return array<array-key, mixed>
     */
    public function strategyOptions(): array
    {
        return self::section($this->strategy(), 'options');
    }

    /**
     * Service name of the resource adapter ("workflow_manager.strategy.repository").
     */
    public function strategyRepository(): ?string
    {
        return self::nonEmptyString($this->strategy(), 'repository');
    }

    /**
     * Service name of the strategy ("workflow_manager.strategy.type").
     */
    public function strategyType(): ?string
    {
        return self::nonEmptyString($this->strategy(), 'type');
    }

    /**
     * The "workflow_manager" section: plugin manager services plus the
     * "strategy" and "navigation" settings.
     *
     * @return array<array-key, mixed>
     */
    public function workflowManager(): array
    {
        return self::section($this->config, 'workflow_manager');
    }

    /**
     * Per-workflow titles and descriptions (the top-level "workflow" key).
     *
     * @return array<array-key, mixed>
     */
    public function workflows(): array
    {
        return self::section($this->config, 'workflow');
    }
}
