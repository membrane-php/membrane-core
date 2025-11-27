<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Filter;

use Generator;
use Membrane\OpenAPI\Filter\QueryStringToArray;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\Renderer\HumanReadable;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Tests\MembraneTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(QueryStringToArray::class)]
#[UsesClass(HumanReadable::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class QueryStringToArrayTest extends MembraneTestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert query string to an array of query parameters';
        $sut = new QueryStringToArray([]);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new QueryStringToArray([]);

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    #[DataProvider('provideNonStringValues')]
    public function itOnlyFiltersStrings(mixed $value): void
    {
        $expected = Result::invalid($value, new MessageSet(null, new Message(
            'String expected, %s provided',
            [gettype($value)],
        )));

        $sut = new QueryStringToArray([]);

        self::assertResultEquals($expected, $sut->filter($value));
    }

    /** @param array<string,array{ style:string, explode:bool }> $parameters */
    #[Test]
    #[DataProvider('provideQueryStringsToFilter')]
    public function itFiltersQueryStrings(
        Result $expected,
        array $parameters,
        string $value
    ): void {
        $sut = new QueryStringToArray($parameters);

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
    public static function provideQueryStringsToFilter(): Generator
    {
        $alwaysResolvable = [
            // type:primitive, style:form
            'color=red' => [
                'color=red',
                'color',
                'form',
                false,
            ],
            'colour=blue' => [
                'colour=blue',
                'colour',
                'form',
                true,
            ],
            'watercolor=lime%20green' => [
                'watercolor=lime green',
                'watercolor',
                'form',
                false,
            ],
            'watercolour=sky%20blue'  => [
                'watercolour=sky blue',
                'watercolour',
                'form',
                true,
            ],

            // type:array, style:form
            'colors=blue,black,brown' => [
                'colors=blue,black,brown',
                'colors',
                'form',
                false,
            ],
            'colours=blue,black,brown' => [
                'colours=blue,black,brown',
                'colours',
                'form',
                true,
            ],
            'watercolors=powder%20blue,saddle%20brown,slate%20grey' => [
                'watercolors=powder blue,saddle brown,slate grey',
                'watercolors',
                'form',
                false,
            ],
            'watercolours=powder%20blue,saddle%20brown,slate%20grey' => [
                'watercolours=powder blue,saddle brown,slate grey',
                'watercolours',
                'form',
                true,
            ],

            // type:object, style:form, explode:false only
            // OpenAPI Specification only defines explode:false in this instance
            'paint=R,100,G,200,B,150' => [
                'paint=R,100,G,200,B,150',
                'paint',
                'form',
                false,
            ],
            'paintpot=light%20grey,0.5,dark%20red,1.0' => [
                'paintpot=light grey,0.5,dark red,1.0',
                'paintpot',
                'form',
                false
            ],

            // type:object only, style:deepObject
            // OpenAPI Specification only defines explode:true in this instance.
            'ink[R]=100&ink[G]=200&ink[B]=150' => [
                'ink[R]=100&ink[G]=200&ink[B]=150',
                'ink',
                'deepObject',
                true
            ],
            'inkwell[navy%20blue]=100&inkwell[standard%20black%20ink]=200' => [
                'inkwell[navy blue]=100&inkwell[standard black ink]=200',
                'inkwell',
                'deepObject',
                true,
            ],

            // type:array, style:spaceDelimited
            // OpenAPI Specification only defines explode:false in this instance.
            'pen=blue black brown' => [
                'pen=blue black brown',
                'pen',
                'spaceDelimited',
                false,
            ],
            'pencil=blue%20black%20brown' => [
                'pencil=blue black brown',
                'pencil',
                'spaceDelimited',
                false,
            ],

            // type:object, style:spaceDelimited
            // OpenAPI Specification only defines explode:false in this instance.
            'graphite=R 100 G 200 B 150' => [
                'graphite=R 100 G 200 B 150',
                'graphite',
                'spaceDelimited',
                false,
            ],
            'lead=R%20100%20G%20200%20B%20150' => [
                'lead=R 100 G 200 B 150',
                'lead',
                'spaceDelimited',
                false,
            ],

            //type:array style:pipeDelimited
            // OpenAPI Specification only defines explode:false in this instance.
            'crayon=blue|black|brown' => [
                'crayon=blue|black|brown',
                'crayon',
                'pipeDelimited',
                false,
            ],
            'crayola=blue%7Cblack%7Cbrown' => [
                'crayola=blue|black|brown',
                'crayola',
                'pipeDelimited',
                false,
            ],

            //type:object style:pipeDelimited
            // OpenAPI Specification only defines explode:false in this instance.
            'chalk=R|100|G|200|B|150' => [
                'chalk=R|100|G|200|B|150',
                'chalk',
                'pipeDelimited',
                false,
            ],
            'charcoal=R%7C100%7CG%7C200%7CB%7C150' => [
                'charcoal=R|100|G|200|B|150',
                'charcoal',
                'pipeDelimited',
                false,
            ],
        ];

        $resolvableIfOnlyOneOfKind = [
            'R=100&G=200&B=150'  => [
                'R=100&G=200&B=150',
                'quill',
                'form',
                true,
            ],
        ];

        $formatResult = fn(
            string $resultString,
            string $paramName,
            string $style,
            bool $explode,
        ) => [$paramName => $resultString];

        $formatParam = fn(
            string $resultString,
            string $paramName,
            string $style,
            bool $explode,
        ) => [$paramName => ['style' => $style, 'explode' => $explode]];

        foreach ($resolvableIfOnlyOneOfKind as $query => $datum) {
            yield $query => [
                Result::noResult($formatResult(...$datum)),
                $formatParam(...$datum),
                $query,
            ];
        }

        foreach ($alwaysResolvable as $query => $datum) {
            yield $query => [
                Result::noResult($formatResult(...$datum)),
                $formatParam(...$datum),
                $query,
            ];

            foreach ($alwaysResolvable as $otherQuery => $otherDatum) {
                if ($query === $otherQuery) {
                    continue;
                }

                yield "$query&$otherQuery" => [
                    Result::noResult(array_merge(
                        $formatResult(...$datum),
                        $formatResult(...$otherDatum),
                    )),
                    array_merge(
                        $formatParam(...$datum),
                        $formatParam(...$otherDatum),
                    ),
                    "$query&$otherQuery",
                ];
            }

            foreach ($resolvableIfOnlyOneOfKind as $otherQuery => $otherDatum) {
                yield "$query&$otherQuery" => [
                    Result::noResult(array_merge(
                        $formatResult(...$datum),
                        $formatResult(...$otherDatum),
                    )),
                    array_merge(
                        $formatParam(...$datum),
                        $formatParam(...$otherDatum),
                    ),
                    "$query&$otherQuery",
                ];
            }
        }
    }
}
