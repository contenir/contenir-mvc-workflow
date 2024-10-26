<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Resource;

interface ResourceInterface
{
    public function getSlug(): string;

    public function getPrimaryKeys(): array;
}
