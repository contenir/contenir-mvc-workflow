<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\TestAsset;

use Contenir\Metadata\MetadataInterface;
use DateTimeInterface;

class MetadataResourceStub extends ResourceStub implements MetadataInterface
{
    public ?DateTimeInterface $metaModified = null;
    public ?DateTimeInterface $metaPublish  = null;
    public ?string $metaTitle               = null;
    public ?string $metaDescription         = null;
    public ?string $metaImage               = null;

    /** @return string|null */
    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    /** @return string|null */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /** @return string|null */
    public function getMetaImage()
    {
        return $this->metaImage;
    }

    public function getMetaModified(): ?DateTimeInterface
    {
        return $this->metaModified;
    }

    public function getMetaPublish(): ?DateTimeInterface
    {
        return $this->metaPublish;
    }
}
