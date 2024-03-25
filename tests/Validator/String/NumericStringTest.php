<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\NumericString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NumericString::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class NumericStringTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        self::assertSame('is a numeric string', (new NumericString())->__toString());
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new NumericString();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function provideValuesToValidate(): array
    {
        $notStringTestCase = fn(mixed $value) => [
            Result::invalid(
                $value,
                new MessageSet(null, new Message('String value expected, %s provided', [gettype($value)]))
            ),
            $value,
        ];

        $notFloatStringTestCase = fn(string $value) => [
            Result::invalid(
                $value,
                new MessageSet(null, new Message('String value must be numeric', []))
            ),
            $value,
        ];

        return [
            'boolean' => $notStringTestCase(true),
            'null' => $notStringTestCase(null),
            'integer' => $notStringTestCase(5),
            'float' => $notStringTestCase(5.5),
            'array' => $notStringTestCase(['5.5']),
            'alphabetical string' => $notFloatStringTestCase('abcdefghijklmnopqrstuvwxyz'),
            'boolean string' => $notFloatStringTestCase('false'),
            'float string with non-numeric prefix' => $notFloatStringTestCase('Sir 3.5'),
            'float string with non-numeric suffix' => $notFloatStringTestCase('3.5 Bsc (Hons)'),
            'integer string' => [Result::valid('5'), '5'],
            'float, that is equivalent to an integer, string' => [Result::valid('5.0'), '5.0'],
            'float string' => [Result::valid('5.5'), '5.5'],
        ];
    }

    #[Test, TestDox('Only strings of floating point numbers will be considered valid')]
    #[DataProvider('provideValuesToValidate')]
    public function validatesFloatStrings(Result $expected, mixed $value): void
    {
        self::assertEquals($expected, (new NumericString())->validate($value));
    }
}
