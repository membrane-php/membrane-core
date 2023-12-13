<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\Shape;

use Membrane\Filter\Shape\Truncate;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Truncate::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class TruncateTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'truncate self to 5 fields or less';
        $sut = new Truncate(5);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new Truncate(5);

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function negativeMaxLengthThrowsError()
    {
        self::expectExceptionMessage('Truncate filter cannot take negative max lengths');

        new Truncate(-1);
    }

    public static function dataSetsWithIncorrectInputs(): array
    {
        $notArrayMessage = 'Truncate filter requires lists, %s given';
        $arrayMessage = 'Truncate filter requires lists, for arrays use Delete';
        return [
            [123, new Message($notArrayMessage, ['integer'])],
            [1.23, new Message($notArrayMessage, ['double'])],
            ['this is a string', new Message($notArrayMessage, ['string'])],
            [true, new Message($notArrayMessage, ['boolean'])],
            [['an' => 'array', 'with' => 'key', 'value' => 'pairs'], new Message($arrayMessage, [])],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectInputs')]
    #[Test]
    public function incorrectInputsReturnInvalid(mixed $input, Message $expectedMessage): void
    {
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));
        $truncate = new Truncate(3);

        $result = $truncate->filter($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function returnInputIfAlreadyBelowMaxLength(): void
    {
        $input = ['a', 'list'];
        $expected = Result::noResult($input);
        $truncate = new Truncate(3);

        $result = $truncate->filter($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsToFilter(): array
    {
        return [
            [
                ['a', 'list'],
                0,
                [],
            ],
            [
                ['a', 'list', 'of', 'five', 'values'],
                2,
                ['a', 'list'],
            ],
        ];
    }

    #[DataProvider('dataSetsToFilter')]
    #[Test]
    public function listsAreTruncatedToMatchMaxLength(array $input, int $maxLength, array $expectedValue): void
    {
        $expected = Result::noResult($expectedValue);
        $truncate = new Truncate($maxLength);

        $result = $truncate->filter($input);

        self::assertEquals($expected, $result);
    }
}
