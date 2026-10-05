<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Container;

use Contenir\Mvc\Workflow\Container\WorkflowConfig;
use ContenirTest\Mvc\Workflow\TestAsset\Container\InMemoryContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkflowConfig::class)]
#[Group('unit')]
final class WorkflowConfigTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function missingSectionsProvider(): array
    {
        return [
            'no config service'       => [null],
            'config is not an array'  => ['config'],
            'sections are not arrays' => [['workflow_manager' => 'x']],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function unusableConfigProvider(): array
    {
        return [
            'no config service'        => [null],
            'config is not an array'   => ['config'],
            'sections are not arrays'  => [['workflow_manager' => 'x', 'workflow' => 'y']],
            'values are empty strings' => [[
                'workflow_manager' => [
                    'strategy'   => ['type' => '', 'repository' => ''],
                    'navigation' => ['name' => ''],
                ],
            ]],
            'values are not strings'   => [[
                'workflow_manager' => [
                    'strategy'   => ['type' => 1, 'repository' => [], 'options' => 'x'],
                    'navigation' => ['name' => false],
                ],
            ]],
        ];
    }

    private static function config(mixed $config): WorkflowConfig
    {
        return WorkflowConfig::from(new InMemoryContainer(null === $config ? [] : ['config' => $config]));
    }

    #[Test]
    #[DataProvider('missingSectionsProvider')]
    public function missingSectionsReadAsEmpty(mixed $config): void
    {
        $config = self::config($config);

        static::assertSame([[], []], [$config->workflowManager(), $config->strategy()]);
    }

    #[Test]
    public function readsEverySetting(): void
    {
        $config = self::config([
            'workflow_manager' => [
                'aliases'    => ['page' => 'PageWorkflow'],
                'strategy'   => [
                    'type'       => 'Strategy',
                    'repository' => 'Repository',
                    'options'    => ['cache_key' => 'key'],
                ],
                'navigation' => ['name' => 'cms'],
            ],
            'workflow'         => ['page' => ['title' => 'Pages']],
        ]);

        static::assertSame(
            ['Strategy', 'Repository', ['cache_key' => 'key'], 'cms', ['page' => ['title' => 'Pages']]],
            [
                $config->strategyType(),
                $config->strategyRepository(),
                $config->strategyOptions(),
                $config->navigationName(),
                $config->workflows(),
            ],
        );
    }

    #[Test]
    #[DataProvider('unusableConfigProvider')]
    public function unusableValuesReadAsUnset(mixed $config): void
    {
        $config = self::config($config);

        static::assertSame(
            [null, null, [], null, []],
            [
                $config->strategyType(),
                $config->strategyRepository(),
                $config->strategyOptions(),
                $config->navigationName(),
                $config->workflows(),
            ],
        );
    }
}
