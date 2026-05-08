<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Strategy;

use Contenir\Mvc\Workflow\Strategy\ResourceStrategy;
use Contenir\Mvc\Workflow\Strategy\ResourceStrategyInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ResourceStrategyInterfaceTest extends TestCase
{
    public function testResourceStrategyImplementsInterface(): void
    {
        $reflection = new ReflectionClass(ResourceStrategy::class);
        $this->assertTrue($reflection->implementsInterface(ResourceStrategyInterface::class));
    }
}
