<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Shape;

use Membrane\Filter\Shape\Rename;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rename::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class RenameTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'rename "a" to "b"';
        $sut = new Rename('a', 'b');

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new Rename('a', 'b');

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function oldAndNewCannotBeEqual(): void
    {
        self::expectException('RuntimeException');
        self::expectExceptionMessage('Rename filter does not accept two equal strings');

        new Rename('a', 'a');
    }

    public static function dataSetsWithIncorrectInputs(): array
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

    #[DataProvider('dataSetsWithIncorrectInputs')]
    #[Test]
    public function incorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $rename = new Rename('old key', 'new key');

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function nonExistentKeysAreIgnored(): void
    {
        $input = ['a' => 1, 'b' => 2];
        $expected = Result::noResult($input);
        $rename = new Rename('c', 'd');

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsToFilter(): array
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

    #[DataProvider('dataSetsToFilter')]
    #[Test]
    public function ifOldKeyExistsThenItIsRenamed(array $input, mixed $old, mixed $new, array $expectedValue): void
    {
        $expected = Result::noResult($expectedValue);
        $rename = new Rename($old, $new);

        $result = $rename->filter($input);

        self::assertEquals($expected, $result);
    }
}
