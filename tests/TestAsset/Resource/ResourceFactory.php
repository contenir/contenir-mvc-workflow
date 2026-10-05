<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Resource;

/**
 * Builds resources with the columns the workflows read.
 */
final class ResourceFactory
{
    /**
     * A visible page resource; $overrides replace or add columns.
     *
     * @param array<string, mixed> $overrides
     * @param array<array-key, mixed> $primaryKeys
     */
    public static function page(
        int $id = 1,
        string $slug = 'page',
        array $overrides = [],
        array $primaryKeys = [],
    ): MagicResource {
        return new MagicResource(
            properties: [
                'resource_type_id' => 1,
                'resource_id'      => $id,
                'title'            => "Page {$id}",
                'visible'          => true,
                'workflow'         => 'page',
                ...$overrides,
            ],
            slug: $slug,
            primaryKeys: [] === $primaryKeys ? ['resource_id' => $id] : $primaryKeys,
        );
    }
}
