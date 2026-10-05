<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Resource;

/**
 * A routable resource (page) in the site tree.
 *
 * Besides these methods, workflows and the strategy read the following
 * properties when present, usually as entity columns: "workflow" (plugin
 * name, default "page"), "title", "title_short", "visible", "children"
 * (iterable of ResourceInterface), "resource_type_id" and "resource_id".
 *
 * @api
 */
interface ResourceInterface
{
    /**
     * Primary key values, passed to controllers as the "resource_id" route default.
     *
     * @return array<array-key, mixed>
     */
    public function getPrimaryKeys(): array;

    /**
     * Slash-separated URL path, without a leading slash.
     */
    public function getSlug(): string;
}
