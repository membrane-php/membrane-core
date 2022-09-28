<?php

declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Pluck;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Shape\Pluck
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class PluckTest extends TestCase
{
    public function dataSetsWithIncorrectInputs(): array
    {
        $notArrayMessage = 'Pluck filter requires arrays, %s given';
        $listMessage = 'Pluck filter requires arrays with key-value pairs';
        return [
            [123, new Message($notArrayMessage, ['integer'])],
            [1.23, new Message($notArrayMessage, ['double'])],
            ['this is a string', new Message($notArrayMessage, ['string'])],
            [true, new Message($notArrayMessage, ['boolean'])],
            [['this', 'is', 'a', 'list'], new Message($listMessage, [])],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectInputs
     */
    public function incorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $pluck = new Pluck('from', 'a', 'b');

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function nonExistentFieldSetIsIgnored(): void
    {
        $input = ['not-from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4];
        $expected = Result::noResult($input);
        $pluck = new Pluck('from', 'a', 'b', 'c');

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function nonExistentFieldNamesAreIgnored(): void
    {
        $input = ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4];
        $expected = Result::noResult($input);
        $pluck = new Pluck('from', 'e', 'f', 'g');

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatPass(): array
    {
        return [
            [
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4],
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 1, 'c' => 3, 'd' => 4],
                'from',
                'a',
                'c',
            ],
            [
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 5, 'd' => 4],
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 1, 'd' => 4],
                'from',
                'a',
                'd',
            ],
            [
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'd' => 4],
                ['from' => ['a' => 1, 'b' => 2, 'c' => 3], 'a' => 1, 'd' => 4],
                'from',
                'a',
                'd',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function correctInputsSuccessfullyPluckValue($input, $expectedValue, $fieldSet, ...$fieldNames): void
    {
        $expected = Result::noResult($expectedValue);
        $pluck = new Pluck($fieldSet, ...$fieldNames);

        $result = $pluck->filter($input);

        self::assertEquals($expected, $result);
    }
}
