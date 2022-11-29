<?php

declare(strict_types=1);

namespace Filter\Type;

use Membrane\Filter\Type\ToNumber;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Type\ToNumber
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class ToNumberTest extends TestCase
{
    public function dataSetsToFilter(): array
    {
        $class = new class {
        };

        return [
            'filters integer' => [
                1,
                Result::valid(1),
            ],
            'filters float' => [
                2.3,
                Result::valid(2.3),
            ],
            'filters integer string' => [
                '4',
                Result::valid(4),
            ],
            'filters float string' => [
                '5.6',
                Result::valid(5.6),
            ],
            'invalidates non-numeric string' => [
                'six',
                Result::invalid(
                    'six',
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['string']))
                ),
            ],
            'invalidates bool' => [
                true,
                Result::invalid(
                    true,
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['boolean']))
                ),
            ],
            'invalidates null' => [
                null,
                Result::invalid(
                    null,
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['NULL']))
                ),
            ],
            'invalidates array' => [
                [1, 2, 3],
                Result::invalid([1, 2, 3],
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['array']))),
            ],
            'invalidates object' => [
                $class,
                Result::invalid(
                    $class,
                    new MessageSet(null, new Message('ToNumber filter expects numeric values, %s passed', ['object']))
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToFilter
     */
    public function validateTest(mixed $value, Result $expected): void
    {
        $sut = new ToNumber();

        $actual = $sut->filter($value);

        self::assertSame($expected->value, $actual->value);
        self::assertEquals($expected, $actual);
    }
}
