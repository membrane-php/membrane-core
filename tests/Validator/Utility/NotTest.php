<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Not;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Not::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class NotTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'must satisfy the opposite of the following: "inverted condition"';
        $invertedValidator = self::createMock(Validator::class);
        $sut = new Not($invertedValidator);

        $invertedValidator->expects($this->once())
            ->method('__toString')
            ->willReturn('inverted condition');

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function toPHPProvider(): array
    {
        return [
            'fails' => [new Fails()],
            'indifferent' => [new Indifferent()],
            'passes' => [new Passes()],
        ];
    }

    #[DataProvider('toPHPProvider')]
    #[Test]
    public function toPHPTest(Validator $invertedValidator): void
    {
        $sut = new Not($invertedValidator);

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToValidate(): array
    {
        return [
            'inverted invalid results will be valid' => [
                'a',
                new Fails(),
                Result::valid('a'),
            ],
            'inverted noResult results will stay noResult' => [
                'b',
                new Indifferent(),
                Result::noResult('b'),
            ],
            'inverted valid results will be invalid' => [
                'c',
                new Passes(),
                Result::invalid(
                    'c',
                    new MessageSet(null, new Message('Inverted validator: %s returned valid', [Passes::class]))
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToValidate')]
    #[Test]
    public function notInvertsInnerValidator(mixed $input, Validator $invertedValidator, Result $expected): void
    {
        $sut = new Not($invertedValidator);

        $actual = $sut->validate($input);

        self::assertEquals($expected, $actual);
    }
}
