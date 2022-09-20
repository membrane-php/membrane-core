<?php

declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Rename;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Shape\Rename
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class RenameTest extends TestCase
{
    public function DataSetsWithIncorrectInputs(): array
    {
        $notArrayMessage = 'Rename filter requires arrays, %s given';
        $listMessage = 'Rename filter requires arrays with key-value pairs';
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
     * @dataProvider DataSetsWithIncorrectInputs
     */
    public function IncorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $rename = new Rename('old key', 'new key');

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function NonExistentKeysAreIgnored(): void
    {
        $input = [1 => 'a', 2 => 'b'];
        $expected = Result::noResult($input);
        $rename = new Rename(3, 4);

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }

    public function DataSetsToFilter(): array
    {
        return [
            [
                ['this' => 'is', 'an' => 'array'],
                'this',
                'that',
                ['that' => 'is', 'an' => 'array']
            ],
            [
                [1 => 'a', 2 => 'b'],
                1,
                'one',
                ['one' => 'a', 2 => 'b']
            ],
            [
                ['a' => 1, 'b' => 'two', 'c' => 3, 'd' => 4],
                'b',
                2,
                ['a' => 1, 2 => 'two', 'c' => 3, 'd' => 4]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsToFilter
     */
    public function IfOldKeyExistsThenItIsRenamed(array $input, mixed $old, mixed $new, array $expectedValue): void
    {
        $expected = Result::noResult($expectedValue);
        $rename = new Rename($old, $new);

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }
}
