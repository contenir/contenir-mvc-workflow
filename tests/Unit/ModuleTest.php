<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit;

use Contenir\Mvc\Workflow\ConfigProvider;
use Contenir\Mvc\Workflow\Module;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversMethod(Module::class, 'getConfig')]
#[Group('unit')]
final class ModuleTest extends TestCase
{
    #[Test]
    public function configMatchesTheConfigProvider(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(
            [
                'service_manager'  => $provider->getDependencyConfig(),
                'workflow_manager' => $provider->getWorkflowManagerConfig(),
                'workflow'         => [],
            ],
            (new Module())->getConfig(),
        );
    }
}
