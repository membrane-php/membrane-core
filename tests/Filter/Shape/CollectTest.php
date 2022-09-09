<?php
declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Collect;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Shape\Collect
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Message
 */
class CollectTest extends TestCase
{
    /**
     * @return array
     */
    public function dataSetsWithIncorrectTypes() : array
    {
        return [
            [
                'this is a string',
                Result::INVALID,
                'Value passed to Collect filter must be an array, %s passed instead',
                ['string'],
            ],
            [
                ['this', 'is', 'a', 'list'],
                Result::INVALID,
                'Value passed to Collect filter was a list, this filter needs string keys to work',
                []
            ],
        ];
    }
    /**
     * @test
     * @dataProvider dataSetsWithIncorrectTypes
     */
    public function IncorrectFilterInputReturnsInvalid($input, $expectedResult, $expectedMessage, $expectedVars) : void
    {
        $collect = new Collect('new field', 'a', 'b');

        $result = $collect->filter($input);

        self::assertEquals($expectedMessage, $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedVars, $result->messageSets[0]?->messages[0]?->vars);
        self::assertEquals($expectedResult, $result->result);
    }

    /**
     * @return array
     */
    public function dataSetsWithCorrectTypes() : array
    {
        return [
            'collecting all items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b', 'c'],
                ['new field' => [1, 2, 3]],
                Result::NO_RESULT,
            ],
            'collecting 2 out of 3 items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b'],
                ['new field' => [1, 2], 'c' => 3],
                Result::NO_RESULT,
            ],
            'collecting an item that is not in the array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['d'],
                ['new field' => [], 'a' => 1, 'b' => 2, 'c' => 3],
                Result::NO_RESULT,
            ],
        ];
    }
    /**
     * @test
     * @dataProvider dataSetsWithCorrectTypes
     */
    public function CorrectFilterInputReturnsResult($input, $fieldsToCollect, $expectedValue, $expectedResult) : void
    {
        $collect = new Collect('new field', ...$fieldsToCollect);

        $result = $collect->filter($input);

        self::assertEquals($expectedValue, $result->value);
        self::assertEquals($expectedResult, $result->result);
    }
}