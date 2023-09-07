<?php

declare(strict_types=1);

namespace Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\Regex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Regex::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class RegexTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'matches the regex: "#^[a-zA-Z]+$#"';
        $sut = new Regex('#^[a-zA-Z]+$#');

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    #[DataProvider('provideRegularExpresions')]
    public function toPHPTest(string $regex): void
    {
        $sut = new Regex('/[abc]/i');

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function provideRegularExpresions(): array
    {
        return [
            'simple regex' => ['/[abc]/i'],
            'regex with special chars' => ['#^[^$\'"]$#']
        ];
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
        $regex = new Regex('');
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message('Regex Validator requires a string, %s given', [$expectedVars])
            )
        );

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatPass(): array
    {
        return [
            ['//', ''],
            ['/[abc]/i', 'B'],
            ['/\d{3}/', '123'],
        ];
    }

    #[DataProvider('dataSetsThatPass')]
    #[Test]
    public function stringsThatMatchPatternReturnValid(string $pattern, string $input): void
    {
        $regex = new Regex($pattern);
        $expected = Result::valid($input);

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }

    public static function dataSetsThatFail(): array
    {
        return [
            ['/abc/', 'ABC'],
            ['/[abc]/', 'd'],
            ['/d{3}/', '12'],
        ];
    }

    #[DataProvider('dataSetsThatFail')]
    #[Test]
    public function stringsThatDoNotMatchPatternReturnInvalid(string $pattern, string $input): void
    {
        $regex = new Regex($pattern);
        $expectedMessage = new Message('String does not match the required pattern %s', [$pattern]);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $regex->validate($input);

        self::assertEquals($expected, $result);
    }
}
