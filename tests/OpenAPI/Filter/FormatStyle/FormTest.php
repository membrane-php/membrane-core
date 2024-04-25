<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Filter\FormatStyle;

use Generator;
use Membrane\OpenAPI\Filter\FormatStyle\Form;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\MembraneTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Form::class)]
#[UsesClass(HumanReadable::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class FormTest extends MembraneTestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'format form style value';
        $sut = new Form('string', true);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new Form('string', true);

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    #[DataProvider('provideNonStringValues')]
    public function itOnlyFiltersStrings(mixed $value): void
    {
        $expected = Result::invalid($value, new MessageSet(null, new Message(
            'Form Filter expects string, %s given',
            [gettype($value)],
        )));

        $sut = new Form('string', true);

        self::assertResultEquals($expected, $sut->filter($value));
    }

    #[Test]
    #[DataProvider('provideFormStringsToFilter')]
    public function itFiltersQueryStrings(
        Result $expected,
        Type $type,
        bool $explode,
        string $value,
    ): void {
        $sut = new Form($type->value, $explode);

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
     *     1: array<string,array{ style:string, explode:bool }>,
     *     2: string,
     * }>
     */
    public static function provideFormStringsToFilter(): Generator
    {
        yield 'type:string, explode:false' => [
            Result::noResult('blue'),
            Type::String,
            false,
            'colour=blue',
        ];

        yield 'type:string, explode:true' => [
            Result::noResult('blue'),
            Type::String,
            true,
            'colour=blue',
        ];

        yield 'type:array, explode:false' => [
            Result::noResult(['blue', 'black', 'brown']),
            Type::Array,
            false,
            'colour=blue,black,brown',
        ];

        yield 'type:array, explode:true' => [
            Result::noResult(['blue', 'black', 'brown']),
            Type::Array,
            true,
            'colour=blue&colour=black&colour=brown',
        ];

        yield 'type:object, explode:false' => [
            Result::noResult(['R', '100', 'G', '200', 'B', '150']),
            Type::Object,
            false,
            'colour=R,100,G,200,B,150',
        ];

        yield 'type:object, explode:true' => [
            Result::noResult(['R', '100', 'G', '200', 'B', '150']),
            Type::Object,
            true,
            'R=100&G=200&B=150',
        ];
    }
}
