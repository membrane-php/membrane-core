<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\IntString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IntString::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class IntStringTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        self::assertSame('is a string of an integer', (new IntString())->__toString());
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new IntString();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function provideValuesToValidate(): array
    {
        $notStringTestCase = fn(mixed $value) => [
            Result::invalid(
                $value,
                new MessageSet(null, new Message('String value expected, %s provided.', [gettype($value)]))
            ),
            $value,
        ];

        $notIntStringCase = fn(string $value) => [
            Result::invalid(
                $value,
                new MessageSet(null, new Message('String value must be an integer.', []))
            ),
            $value,
        ];

        return [
            'boolean' => $notStringTestCase(true),
            'null' => $notStringTestCase(null),
            'integer' => $notStringTestCase(5),
            'float' => $notStringTestCase(5.5),
            'array' => $notStringTestCase(['5.5']),
            'alphabetical string' => $notIntStringCase('abcdefghijklmnopqrstuvwxyz'),
            'boolean string' => $notIntStringCase('false'),
            'float string with non-numeric prefix' => $notIntStringCase('Sir 4'),
            'float string with non-numeric suffix' => $notIntStringCase('4 Bsc (Hons)'),
            'float string' => $notIntStringCase('5.5'),
            'float, that is equivalent to an integer, string' => $notIntStringCase('5.0'),
            'integer string' => [Result::valid('5'), '5'],
        ];
    }

    #[Test, TestDox('Only strings of floating point numbers will be considered valid')]
    #[DataProvider('provideValuesToValidate')]
    public function validatesFloatStrings(Result $expected, mixed $value): void
    {
        self::assertEquals($expected, (new IntString())->validate($value));
    }
}
