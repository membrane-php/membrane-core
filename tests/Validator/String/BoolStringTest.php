<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\String\BoolString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BoolString::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class BoolStringTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        self::assertSame('is a string of a boolean', (new BoolString())->__toString());
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new BoolString();

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

        $notBoolStringTestCase = fn(string $value) => [
            Result::invalid(
                $value,
                new MessageSet(null, new Message('String value must be a boolean.', []))
            ),
            $value,
        ];

        return [
            'boolean' => $notStringTestCase(true),
            'null' => $notStringTestCase(null),
            'integer' => $notStringTestCase(5),
            'float' => $notStringTestCase(5.5),
            'array' => $notStringTestCase(['5.5']),
            'alphabetical string' => $notBoolStringTestCase('abcdefghijklmnopqrstuvwxyz'),
            'float string' => $notBoolStringTestCase('5.5'),
            'int string' => $notBoolStringTestCase('5'),
            'int equivalent of true' => $notBoolStringTestCase('1'),
            'int equivalent of false' => $notBoolStringTestCase('0'),
            'boolish string "off"' => $notBoolStringTestCase('off'),
            'boolish string "on"' => $notBoolStringTestCase('on'),
            'boolish string "no"' => $notBoolStringTestCase('no'),
            'boolish string "yes"' => $notBoolStringTestCase('yes'),
            'bool (true) string' => [Result::valid('true'), 'true'],
            'bool (TruE) string' => [Result::valid('TruE'), 'TruE'],
            'bool (false) string' => [Result::valid('false'), 'false'],
        ];
    }

    #[Test, TestDox('Only strings of floating point numbers will be considered valid')]
    #[DataProvider('provideValuesToValidate')]
    public function validatesFloatStrings(Result $expected, mixed $value): void
    {
        self::assertEquals($expected, (new BoolString())->validate($value));
    }
}
