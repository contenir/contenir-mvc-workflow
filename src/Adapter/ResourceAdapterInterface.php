<?php

declare(strict_types=1);

namespace Contenir\Mvc\Workflow\Adapter;

interface ResourceAdapterInterface
{
    public function getWorkflowResources();

    public function getSlug(): string;

    public function getPrimaryKeys(): array;
}
