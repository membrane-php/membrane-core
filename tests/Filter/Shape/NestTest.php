<?php

declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Nest;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Shape\Nest
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class NestTest extends TestCase
{
    public function dataSetsWithIncorrectTypes(): array
    {
        return [
            [
                'this is a string',
                'Value passed to Nest filter must be an array, %s passed instead',
                ['string'],
            ],
            [
                ['this', 'is', 'a', 'list'],
                'Value passed to Nest filter was a list, this filter needs string keys to work',
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
        $nest = new Nest('new field', 'a', 'b');
        $expected = Result::invalid($input, new MessageSet(null, new Message($expectedMessage, $expectedVars)));

        $result = $nest->filter($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsWithCorrectTypes(): array
    {
        return [
            'nesting all items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b', 'c'],
                ['new field' => ['a' => 1, 'b' => 2, 'c' => 3]],
            ],
            'nesting 2 out of 3 items in array' => [
                ['a' => 1, 'b' => 2, 'c' => 3],
                ['a', 'b'],
                ['new field' => ['a' => 1, 'b' => 2], 'c' => 3],
            ],
            'nesting an item that is not in the array' => [
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
        $nest = new Nest('new field', ...$fieldsToCollect);
        $expected = Result::noResult($expectedValue);

        $result = $nest->filter($input);

        self::assertEquals($expected, $result);
    }
}
