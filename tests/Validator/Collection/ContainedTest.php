<?php

declare(strict_types=1);

namespace Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Contained;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Collection\Contained
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ContainedTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'no values' => [
                [],
                'will return invalid',
            ],
            'single integer value' => [
                [1],
                'is one of the following values: 1',
            ],
            'single string value' => [
                ['a'],
                'is one of the following values: "a"',
            ],
            'multiple fixed values' => [
                [1, 'a', true],
                'is one of the following values: 1, "a", true',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringTest(array $enum, string $expected): void
    {
        $sut = new Contained($enum);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'empty array' => [new Contained([])],
            'array with 1 value' => [new Contained(['a'])],
            'array with 3 values' => [new Contained(['a', 1, true])],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToPHPString
     */
    public function toPHPTest(Contained $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToValidate(): array
    {
        return [
            'value contained in array' => [
                true,
                [true, false],
                Result::valid(true),
            ],
            'value not contained in array' => [
                'Where am I?',
                ['Not', 'in', 'here'],
                Result::invalid(
                    'Where am I?',
                    new MessageSet(
                        null,
                        new Message('Contained validator did not find value within array', [['Not', 'in', 'here']])
                    )
                ),
            ],
            'value of different type than array items' => [
                1,
                ['1', '2', '3'],
                Result::invalid(
                    1,
                    new MessageSet(
                        null,
                        new Message('Contained validator did not find value within array', [['1', '2', '3']])
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(mixed $value, array $enum, Result $expected): void
    {
        $sut = new Contained($enum);

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }
}
