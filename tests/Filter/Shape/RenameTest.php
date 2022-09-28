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
    /**
     * @test
     */
    public function oldAndNewCannotBeEqual(): void
    {
        self::expectException('RuntimeException');
        self::expectExceptionMessage('Rename filter does not accept two equal strings');

        new Rename('a', 'a');
    }

    public function dataSetsWithIncorrectInputs(): array
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
     * @dataProvider dataSetsWithIncorrectInputs
     */
    public function incorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $rename = new Rename('old key', 'new key');

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function nonExistentKeysAreIgnored(): void
    {
        $input = ['a' => 1, 'b' => 2];
        $expected = Result::noResult($input);
        $rename = new Rename('c', 'd');

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsToFilter(): array
    {
        return [
            [
                ['this' => 'is', 'an' => 'array'],
                'this',
                'that',
                ['that' => 'is', 'an' => 'array'],
            ],
            [
                ['this' => 'is', 'an' => 'array'],
                'this',
                'an',
                ['an' => 'is'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToFilter
     */
    public function ifOldKeyExistsThenItIsRenamed(array $input, mixed $old, mixed $new, array $expectedValue): void
    {
        $expected = Result::noResult($expectedValue);
        $rename = new Rename($old, $new);

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }
}
