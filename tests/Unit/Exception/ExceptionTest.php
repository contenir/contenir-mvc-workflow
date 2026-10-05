<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Exception;

use Contenir\Mvc\Workflow\Exception\ExceptionInterface;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Exception\RuntimeException;
use InvalidArgumentException as SplInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException as SplRuntimeException;
use Throwable;

#[CoversClass(InvalidArgumentException::class)]
#[CoversClass(RuntimeException::class)]
#[Group('unit')]
final class ExceptionTest extends TestCase
{
    /**
     * @return array<string, array{Throwable, class-string}>
     */
    public static function exceptionProvider(): array
    {
        return [
            'invalid argument' => [new InvalidArgumentException('boom'), SplInvalidArgumentException::class],
            'runtime'          => [new RuntimeException('boom'), SplRuntimeException::class],
        ];
    }

    /**
     * @param class-string $splType
     */
    #[Test]
    #[DataProvider('exceptionProvider')]
    public function exceptionsCanBeCaughtByPackageInterfaceOrSplType(Throwable $exception, string $splType): void
    {
        static::assertSame(
            [true, true],
            [$exception instanceof ExceptionInterface, $exception instanceof $splType],
        );
    }
}
