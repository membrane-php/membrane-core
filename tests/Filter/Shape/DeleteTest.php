<?php

declare(strict_types=1);

namespace Filter\Shape;

use Membrane\Filter\Shape\Delete;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\Shape\Delete
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class DeleteTest extends TestCase
{
    public function dataSetsWithIncorrectInputs(): array
    {
        $notArrayMessage = 'Delete filter requires arrays, %s given';
        $listMessage = 'Delete filter requires arrays, for lists use Truncate';
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
        $delete = new Delete();

        $result = $delete->filter($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsToFilter(): array
    {
        return [
            [
                ['this' => 'is', 'an' => 'array'],
                ['non-existent-field-name'],
                ['this' => 'is', 'an' => 'array'],
            ],
            [
                ['this' => 'is', 'an' => 'array'],
                ['this'],
                ['an' => 'array'],
            ],
            [
                ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4],
                ['a', 'c', 'e'],
                ['b' => 2, 'd' => 4],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToFilter
     */
    public function listsAreTruncatedToMatchMaxLength(array $input, array $fieldNames, array $expectedValue): void
    {
        $expected = Result::noResult($expectedValue);
        $delete = new Delete(...$fieldNames);

        $result = $delete->filter($input);

        self::assertEquals($expected, $result);
    }
}
