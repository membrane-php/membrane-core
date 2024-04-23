<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Filter\FormatStyle;

use Generator;
use Membrane\OpenAPI\Filter\FormatStyle\SpaceDelimited;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\MembraneTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(SpaceDelimited::class)]
#[UsesClass(HumanReadable::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class SpaceDelimitedTest extends MembraneTestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'format spaceDelimited style value';
        $sut = new SpaceDelimited();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new SpaceDelimited();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    #[DataProvider('provideNonStringValues')]
    public function itOnlyFiltersStrings(mixed $value): void
    {
        $expected = Result::invalid($value, new MessageSet(null, new Message(
            'SpaceDelimited Filter expects string, %s given',
            [gettype($value)],
        )));

        $sut = new SpaceDelimited();

        self::assertResultEquals($expected, $sut->filter($value));
    }

    #[Test]
    #[DataProvider('provideSpaceDelimitedStringsToFilter')]
    public function itFiltersQueryStrings(
        Result $expected,
        string $value,
    ): void {
        $sut = new SpaceDelimited();

        $actual = $sut->filter($value);

        self::assertResultEquals($expected, $actual);
    }

    /** @return array<string,array{0:mixed}> */
    public static function provideNonStringValues(): array
    {
        return [
            'integer' => [5],
            'float' => [5.0],
            'boolean' => [true],
            'null' => [null],
            'array' => [[]],
            'object' => [new class () {
            }],
        ];
    }

    /**
     * @return \Generator<array{
     *     0: Result,
     *     1: string,
     * }>
     */
    public static function provideSpaceDelimitedStringsToFilter(): Generator
    {
        yield 'type:array, explode:false' => [
            Result::noResult(['blue', 'black', 'brown']),
            'colour=blue black brown',
        ];

        yield 'type:object, explode:false' => [
            Result::noResult(['R', '100', 'G', '200', 'B', '150']),
            'R 100 G 200 B 150',
        ];
    }
}
