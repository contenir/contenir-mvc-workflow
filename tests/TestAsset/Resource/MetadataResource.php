<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset\Resource;

use Contenir\Metadata\MetadataInterface;
use DateTimeInterface;

/**
 * A resource that also carries page metadata, as contenir-resource entities do.
 */
final class MetadataResource extends MagicResource implements MetadataInterface
{
    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(
        array $properties = [],
        private readonly ?DateTimeInterface $modified = null,
    ) {
        parent::__construct($properties);
    }

    public function getMetaDescription(): ?string
    {
        return null;
    }

    public function getMetaImage(): ?string
    {
        return null;
    }

    public function getMetaModified(): ?DateTimeInterface
    {
        return $this->modified;
    }

    public function getMetaPublish(): ?DateTimeInterface
    {
        return null;
    }

    public function getMetaTitle(): ?string
    {
        return null;
    }
}
