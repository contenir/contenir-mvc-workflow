<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Resource;

use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use ContenirTest\Mvc\Workflow\TestAsset\ResourceStub;
use PHPUnit\Framework\TestCase;

class ResourceInterfaceTest extends TestCase
{
    public function testStubImplementsResourceInterface(): void
    {
        $resource = new ResourceStub();
        $this->assertInstanceOf(ResourceInterface::class, $resource);

        $resource->setSlug('hello-world');
        $resource->setPrimaryKeys(['id' => 1]);

        $this->assertSame('hello-world', $resource->getSlug());
        $this->assertSame(['id' => 1], $resource->getPrimaryKeys());
    }

    public function testAdapterInterfaceCanBeImplemented(): void
    {
        $adapter = new class implements ResourceAdapterInterface {
            public function getWorkflowResources(): iterable
            {
                return [];
            }
        };

        $this->assertInstanceOf(ResourceAdapterInterface::class, $adapter);
        $this->assertSame([], (array) $adapter->getWorkflowResources());
    }
}
