<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\DateString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DateString::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class DateStringTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'matches the DateTime format: "#^[a-zA-Z]+$#"';
        $sut = new DateString('#^[a-zA-Z]+$#');

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new DateString('Y-m-d');

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsWithIncorrectTypes(): array
    {
        return [
            [123, 'integer'],
            [1.23, 'double'],
            [[], 'array'],
            [true, 'boolean'],
            [null, 'NULL'],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectTypes')]
    #[Test]
    public function incorrectTypesReturnInvalidResults($input, $expectedVars): void
    {
        $dateString = new DateString('');
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('DateString Validator requires a string, %s given', [$expectedVars])
            )
        );

        $result = $dateString->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatPass(): array
    {
        return [
            ['', ''],
            ['Y-m-d', '1970-01-01'],
            ['d-M-y', '20-feb-22'],
        ];
    }

    #[DataProvider('dataSetsThatPass')]
    #[Test]
    public function stringsThatMatchFormatReturnValid(string $format, string $input): void
    {
        $dateString = new DateString($format);
        $expected = Result::valid($input);

        $result = $dateString->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            ['Y-m-d', '1990 June 15'],
            ['Y-m', '01-April'],
            ['Y', ''],
        ];
    }

    #[DataProvider('dataSetsThatFail')]
    #[Test]
    public function stringsThatDoNotMatchFormatReturnInvalid(string $format, string $input): void
    {
        $dateString = new DateString($format);
        $expectedMessage = new Message('String does not match the required format %s', [$format]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $dateString->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test, TestDox('Tests that a date which matches the format string but is otherwise invalid fails validation')]
    public function testInvalidDateFailsValidationInStrictMode(): void
    {
        $format = 'Y-m-d';
        $value = '2022-13-05';

        $expectedMessage = new Message('String does not represent a valid date in format %s', [$format]);
        $expected = Result::invalid($value, new MessageSet(null, $expectedMessage));

        $sut = new DateString($format, true);

        $result = $sut->validate($value);
        self::assertEquals($expected, $result);
    }

    #[Test, TestDox('Tests a format which cannot be used in strict mode still validates properly in non-strict mode')]
    public function testDatesPassInNonStrictMode(): void
    {
        $sut = new DateString('Y-m-d|', false);

        self::assertTrue($sut->validate('2022-05-13')->isValid());
    }
}
