<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Resource;

use Contenir\Mvc\Workflow\Resource\ResourceInterface;

use function array_key_exists;

/**
 * A resource whose columns are magic properties, like a contenir-db-model entity.
 */
class MagicResource implements ResourceInterface
{
    /**
     * @param array<string, mixed> $properties
     * @param array<array-key, mixed> $primaryKeys
     */
    public function __construct(
        private array $properties = [],
        private string $slug = 'default-slug',
        private array $primaryKeys = ['resource_id' => 100],
    ) {}

    /**
     * @return array<array-key, mixed>
     */
    public function getPrimaryKeys(): array
    {
        return $this->primaryKeys;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function __get(string $name): mixed
    {
        return $this->properties[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->properties);
    }
}
