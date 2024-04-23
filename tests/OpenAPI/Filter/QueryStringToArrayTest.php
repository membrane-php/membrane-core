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
            'color' => [
                ['color' => ['style' => 'form', 'explode' => false]],
                'color=blue',
            ],
            'colour' => [
                ['colour' => ['style' => 'form', 'explode' => false]],
                'colour=blue',
            ],
            'watercolor' => [
                ['watercolor' => ['style' => 'form', 'explode' => true]],
                'watercolor=blue',
            ],
            'watercolour' => [
                ['watercolour' => ['style' => 'form', 'explode' => true]],
                'watercolour=blue',
            ],

            // type:array, style:form
            'colors' => [
                ['colors' => ['style' => 'form', 'explode' => false]],
                'colors=blue,black,brown',
            ],
            'colours' => [
                ['colours' => ['style' => 'form', 'explode' => false]],
                'colours=blue,black,brown',
            ],
            'watercolors' => [
                ['watercolors' => ['style' => 'form', 'explode' => true]],
                'watercolors=blue&watercolors=black&watercolors=brown',
            ],
            'watercolours' => [
                ['watercolours' => ['style' => 'form', 'explode' => true]],
                'watercolours=blue&watercolours=black&watercolours=brown',
            ],

            // type:object, style:form, explode:false only
            'paint' => [
                ['paint' => ['style' => 'form', 'explode' => false]],
                'paint=R,100,G,200,B,150',
            ],
            'paintpot' => [
                ['paintpot' => ['style' => 'form', 'explode' => false]],
                'paintpot=R,100,G,200,B,150',
            ],

            // type:object only, style:deepObject
            'ink' => [
                ['ink' => ['style' => 'deepObject', 'explode' => true]],
                'ink[R]=100&ink[G]=200&ink[B]=150',
            ],
            'inkwell' => [
                ['inkwell' => ['style' => 'deepObject', 'explode' => true]],
                'inkwell[R]=100&inkwell[G]=200&inkwell[B]=150',
            ],

            // type:array, style:spaceDelimited
            'pen' => [
                ['pen' => ['style' => 'spaceDelimited', 'explode' => false]],
                'pen=blue black brown',
            ],
            'pencil' => [
                ['pencil' => ['style' => 'spaceDelimited', 'explode' => false]],
                'pencil=blue black brown',
            ],

            // type:object, style:spaceDelimited
            'graphite' => [
                ['graphite' => ['style' => 'spaceDelimited', 'explode' => false]],
                'graphite=R 100 G 200 B 150',
            ],
            'lead' => [
                ['lead' => ['style' => 'spaceDelimited', 'explode' => false]],
                'lead=R 100 G 200 B 150',
            ],

            //type:array style:pipeDelimited
            'crayon' => [
                ['crayon' => ['style' => 'pipeDelimited', 'explode' => false]],
                'crayon=blue|black|brown',
            ],
            'crayola' => [
                ['crayola' => ['style' => 'pipeDelimited', 'explode' => false]],
                'crayola=blue|black|brown',
            ],

            //type:object style:pipeDelimited
            'chalk' => [
                ['chalk' => ['style' => 'pipeDelimited', 'explode' => false]],
                'chalk=R|100|G|200|B|150',
            ],
            'charcoal' => [
                ['charcoal' => ['style' => 'pipeDelimited', 'explode' => false]],
                'charcoal=R|100|G|200|B|150',
            ]
        ];

        $resolvableIfOnlyOneOfKind = [
            'quill' => [
                ['quill' => ['style' => 'form', 'explode' => true]],
                'R=100&G=200&B=150',
            ],
        ];

        foreach ($resolvableIfOnlyOneOfKind as $name => $parameter) {
            yield "$name" => [
                Result::noResult([$name => $parameter[1]]),
                $parameter[0],
                $parameter[1],
            ];
        }

        foreach ($alwaysResolvable as $name => $parameter) {
            yield "$name" => [
                Result::noResult([$name => $parameter[1]]),
                $parameter[0],
                $parameter[1],
            ];

            foreach ($alwaysResolvable as $otherName => $otherParameter) {
                if ($name === $otherName) {
                    continue;
                }

                yield "$name and $otherName" => [
                    Result::noResult([
                        $name => $parameter[1],
                        $otherName => $otherParameter[1]
                    ]),
                    array_merge($parameter[0], $otherParameter[0]),
                    implode('&', [$parameter[1], $otherParameter[1]])
                ];
            }

            foreach ($resolvableIfOnlyOneOfKind as $otherName => $otherParameter) {
                yield "$name and $otherName" => [
                    Result::noResult([
                        $name => $parameter[1],
                        $otherName => $otherParameter[1]
                    ]),
                    array_merge($otherParameter[0], $parameter[0]),
                    implode('&', [$otherParameter[1], $parameter[1]])
                ];
            }
        }
    }
}
