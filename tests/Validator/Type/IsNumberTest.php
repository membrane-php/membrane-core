<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsNumber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IsNumber::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IsNumberTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'is a number';
        $sut = new IsNumber();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IsNumber();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToValidate(): array
    {
        $class = new class {
        };

        return [
            'validates integer' => [
                1,
                Result::valid(1),
            ],
            'validates float' => [
                2.3,
                Result::valid(2.3),
            ],
            'invalidates integer string' => [
                '4',
                Result::invalid(
                    '4',
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['string']))
                ),
            ],
            'invalidates float string' => [
                '5.6',
                Result::invalid(
                    '5.6',
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['string']))
                ),
            ],
            'invalidates non-numeric string' => [
                'six',
                Result::invalid(
                    'six',
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['string']))
                ),
            ],
            'invalidates bool' => [
                true,
                Result::invalid(
                    true,
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['boolean']))
                ),
            ],
            'invalidates null' => [
                null,
                Result::invalid(
                    null,
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['NULL']))
                ),
            ],
            'invalidates array' => [
                [1, 2, 3],
                Result::invalid(
                    [1, 2, 3],
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['array']))
                ),
            ],
            'invalidates object' => [
                $class,
                Result::invalid(
                    $class,
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['object']))
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToValidate')]
    #[Test]
    public function validateTest(mixed $value, Result $expected): void
    {
        $sut = new IsNumber();

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }
}
