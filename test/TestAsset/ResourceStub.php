<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset;

use Contenir\Mvc\Workflow\Resource\ResourceInterface;

// phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCapsProperty
class ResourceStub implements ResourceInterface
{
    public ?string $title         = 'Default Title';
    public ?string $title_short   = null;
    public ?int $resource_type_id = 1;
    public ?int $resource_id      = 100;
    public bool $visible          = true;
    public string $workflow       = 'page';
    public iterable $children     = [];
    private string $slug          = 'default-slug';
    private array $primaryKeys    = ['my-key'];

    public function __construct(array $properties = [])
    {
        foreach ($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setPrimaryKeys(array $keys): void
    {
        $this->primaryKeys = $keys;
    }

    public function getPrimaryKeys(): array
    {
        return $this->primaryKeys;
    }
}
