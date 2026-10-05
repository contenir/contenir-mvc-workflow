<?php

declare(strict_types=1);

namespace ContenirTest\Mvc\Workflow\Unit\Resource;

use ArrayIterator;
use Contenir\Mvc\Workflow\Exception\InvalidArgumentException;
use Contenir\Mvc\Workflow\Resource\ResourceProperty;
use ContenirTest\Mvc\Workflow\TestAsset\Resource\MagicResource;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(ResourceProperty::class)]
#[Group('unit')]
final class ResourcePropertyTest extends TestCase
{
    /**
     * @return array<string, array{mixed, bool}>
     */
    public static function boolProvider(): array
    {
        return [
            'true'       => [true, true],
            'database 1' => ['1', true],
            'database 0' => ['0', false],
            'missing'    => [null, false],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function noChildrenProvider(): array
    {
        return [
            'missing'      => [null],
            'not iterable' => ['none'],
            'empty'        => [[]],
        ];
    }

    /**
     * @return array<string, array{mixed, string|null}>
     */
    public static function stringProvider(): array
    {
        return [
            'string'  => ['About', 'About'],
            'empty'   => ['', ''],
            'integer' => [42, '42'],
            'missing' => [null, null],
            'object'  => [new stdClass(), null],
        ];
    }

    #[Test]
    #[DataProvider('boolProvider')]
    public function boolReadsTruthiness(mixed $value, bool $expected): void
    {
        static::assertSame($expected, ResourceProperty::bool(new MagicResource(['visible' => $value]), 'visible'));
    }

    #[Test]
    public function childrenAreListedFromAnyIterable(): void
    {
        $first  = new MagicResource();
        $second = new MagicResource();

        $children = ResourceProperty::children(new MagicResource([
            'children' => new ArrayIterator(['a' => $first, 'b' => $second]),
        ]));

        static::assertSame([$first, $second], $children);
    }

    #[Test]
    #[DataProvider('noChildrenProvider')]
    public function childrenIsEmptyWithoutChildren(mixed $children): void
    {
        static::assertSame([], ResourceProperty::children(new MagicResource(['children' => $children])));
    }

    #[Test]
    public function childrenMustBeResources(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Resource children must implement Contenir\Mvc\Workflow\Resource\ResourceInterface, stdClass given',
        );

        ResourceProperty::children(new MagicResource(['children' => [new stdClass()]]));
    }

    #[Test]
    #[DataProvider('stringProvider')]
    public function stringReadsScalarPropertiesAsStrings(mixed $value, ?string $expected): void
    {
        static::assertSame($expected, ResourceProperty::string(new MagicResource(['title' => $value]), 'title'));
    }

    #[Test]
    public function valueIsNullForMissingProperties(): void
    {
        static::assertNull(ResourceProperty::value(new MagicResource(), 'title'));
    }
}
