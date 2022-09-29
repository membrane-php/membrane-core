<?php

declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Collect;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Shape\Collect
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class CollectTest extends TestCase
{
    public function dataSetsWithIncorrectTypes(): array
    {
        return [
            [
                'this is a string',
                'Value passed to Collect filter must be an array, %s passed instead',
                ['string'],
            ],
            [
                ['this', 'is', 'a', 'list'],
                'Value passed to Collect filter was a list, this filter needs string keys to work',
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectTypes
     */
    public function incorrectFilterInputReturnsInvalid(mixed $input, string $expectedMessage, array $expectedVars): void
    {
        $collect = new Collect('new field', 'a', 'b');
        $expected = Result::invalid($input, new MessageSet(null, new Message($expectedMessage, $expectedVars)));

        $result = $collect->filter($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithCorrectTypes(): array
    {
        return [
            'collecting all items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b', 'c'],
                ['new field' => [1, 2, 3]],
            ],
            'collecting 2 out of 3 items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b'],
                ['new field' => [1, 2], 'c' => 3],
            ],
            'collecting an item that is not in the array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['d'],
                ['new field' => [], 'a' => 1, 'b' => 2, 'c' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithCorrectTypes
     */
    public function correctFilterInputReturnsResult(array $input, array $fieldsToCollect, array $expectedValue): void
    {
        $collect = new Collect('new field', ...$fieldsToCollect);
        $expected = Result::noResult($expectedValue);

        $result = $collect->filter($input);

        self::assertEquals($expected, $result);
    }
}
