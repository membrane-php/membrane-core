<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Shape;

use Membrane\Filter\Shape\Delete;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Delete::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class DeleteTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'no fields' => [
                [],
                '',
            ],
            'single field' => [
                ['a'],
                'delete "a" from self',
            ],
            'multiple fields' => [
                ['a', 'b', 'c'],
                'delete "a", "b", "c" from self',
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(array $fields, string $expected): void
    {
        $sut = new Delete(...$fields);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no fields' => [new Delete(),],
            'one field' => [new Delete('a'),],
            'multiple fields' => [new Delete('a', 'b', 'c'),],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(Delete $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectInputs(): array
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

    #[DataProvider('dataSetsWithIncorrectInputs')]
    #[Test]
    public function incorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $delete = new Delete();

        $result = $delete->filter($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsToFilter(): array
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

    #[DataProvider('dataSetsToFilter')]
    #[Test]
    public function listsAreTruncatedToMatchMaxLength(array $input, array $fieldNames, array $expectedValue): void
    {
        $expected = Result::noResult($expectedValue);
        $delete = new Delete(...$fieldNames);

        $result = $delete->filter($input);

        self::assertEquals($expected, $result);
    }
}
