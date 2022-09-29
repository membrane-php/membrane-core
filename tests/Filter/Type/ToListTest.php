<?php

declare(strict_types=1);

namespace Filter\Type;

use Membrane\Filter\Type\ToList;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Type\ToList
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ToListTest extends TestCase
{
    public function dataSetsWithAcceptableInputs(): array
    {
        return [
            [[], []],
            [['this', 'is', 'a', 'list'], ['this', 'is', 'a', 'list']],
            [['even' => 'this', 'array' => 'is', 'becomes' => 'a', 'simple' => 'list'], ['this', 'is', 'a', 'list']],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithAcceptableInputs
     */
    public function acceptableTypesReturnListValues($input, $expectedValue): void
    {
        $toList = new ToList();
        $expected = Result::noResult($expectedValue);

        $result = $toList->filter($input);

        self::assertSame($expected->value, $result->value);
        self::assertEquals($expected->result, $result->result);
    }

    public function dataSetsWithUnacceptableInputs(): array
    {
        $message = 'ToList filter only accepts arrays, %s given';
        $class = new class () {
        };

        return [
            [
                'string',
                new Message($message, ['string']),
            ],
            [
                123,
                new Message($message, ['integer']),
            ],
            [
                1.23,
                new Message($message, ['double']),
            ],
            [
                true,
                new Message($message, ['boolean']),
            ],
            [
                null,
                new Message($message, ['NULL']),
            ],
            [
                $class,
                new Message($message, ['object']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithUnacceptableInputs
     */
    public function unacceptableTypesReturnInvalid($input, $expectedMessage): void
    {
        $toList = new ToList();
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $toList->filter($input);

        self::assertEquals($expected, $result);
    }
}
