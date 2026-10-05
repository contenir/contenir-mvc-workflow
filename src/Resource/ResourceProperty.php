<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Resource;

use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;

use function get_debug_type;
use function is_iterable;
use function is_scalar;
use function sprintf;

/**
 * Reads the conventional, usually magic, properties of a resource entity
 * (title, title_short, visible, children, workflow, resource_type_id,
 * resource_id) that ResourceInterface does not declare.
 *
 * @internal
 */
final class ResourceProperty
{
    /**
     * The property's truthiness, as the navigation "visible" flag reads it.
     *
     * @param non-empty-string $name
     *
     * @mago-expect analysis:mixed-operand Resource properties are read for truthiness, as in 1.x.
     */
    public static function bool(ResourceInterface $resource, string $name): bool
    {
        return (bool) self::value($resource, $name);
    }

    /**
     * The resource's children, or an empty list when it has none.
     *
     * @return list<ResourceInterface>
     *
     * @throws InvalidArgumentException When a child is not a ResourceInterface.
     *
     * @mago-expect analysis:mixed-assignment Resource properties are untyped; narrowed here.
     */
    public static function children(ResourceInterface $resource): array
    {
        $children = self::value($resource, 'children');
        if (! is_iterable($children)) {
            return [];
        }

        $list = [];
        foreach ($children as $child) {
            if (! $child instanceof ResourceInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Resource children must implement %s, %s given',
                    ResourceInterface::class,
                    get_debug_type($child),
                ));
            }

            $list[] = $child;
        }

        return $list;
    }

    /**
     * The property as a string, or null when it is not set or not scalar.
     *
     * @param non-empty-string $name
     *
     * @mago-expect analysis:mixed-assignment Resource properties are untyped; narrowed here.
     */
    public static function string(ResourceInterface $resource, string $name): ?string
    {
        $value = self::value($resource, $name);

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * The property's value, or null when it is not set.
     *
     * @param non-empty-string $name
     *
     * @mago-expect analysis:string-member-selector Resource properties are dynamic by design.
     */
    public static function value(ResourceInterface $resource, string $name): mixed
    {
        return $resource->{$name} ?? null;
    }
}
