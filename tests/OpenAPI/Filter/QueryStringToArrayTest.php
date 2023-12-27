<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Filter;

use Generator;
use Membrane\OpenAPI\Filter\QueryStringToArray;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueryStringToArray::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class QueryStringToArrayTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert query string to an array of query parameters';
        $sut = new QueryStringToArray();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new QueryStringToArray();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function provideValuesToFilter(): Generator
    {
        foreach (
            [
                'integer value' => 5,
                'float value' => 5.0,
                'boolean value' => true,
                'null value' => null,
                'array value' => [],
                'object value' => new class () {
                }
            ] as $wrongType => $value
        ) {
            yield $wrongType => [
                $value,
                Result::invalid(
                    $value,
                    new MessageSet(
                        null,
                        new Message(
                            'String expected, %s provided',
                            [gettype($value)]
                        )
                    )
                )
            ];
        }

        yield 'string parameter' => [
            'name=ben',
            Result::noResult(['name' => ['ben']])
        ];

        yield 'int parameter' => [
            'id=1',
            Result::noResult(['id' => ['1']])
        ];

        yield 'bool parameter' => [
            'dark-mode=false',
            Result::noResult(['dark-mode' => ['false']])
        ];

        yield 'array parameter, explode:false' => [
            'colour=blue,black,brown',
            Result::noResult(['colour' => ['blue,black,brown']])
        ];

        yield 'array parameter, explode:true' => [
            'colour=blue&colour=black&colour=brown',
            Result::noResult(['colour' => ['blue', 'black', 'brown']])
        ];

        yield 'object parameter, explode:false' => [
            'colour=R,100,G,200,B,150',
            Result::noResult(['colour' => ['R,100,G,200,B,150']])
        ];

        yield 'object parameter, explode:true' => [
            'color=R,100&color=G,200&color=B,150',
            Result::noResult(['color' => ['R,100', 'G,200', 'B,150']])
        ];
    }

    #[DataProvider('provideValuesToFilter')]
    #[Test]
    public function itFiltersQueryStrings(mixed $value, Result $expected): void
    {
        $sut = new QueryStringToArray();

        $actual = $sut->filter($value);

        self::assertEquals($expected, $actual);
    }
}
