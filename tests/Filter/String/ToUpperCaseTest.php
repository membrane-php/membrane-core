<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\String;

use Membrane\Filter\String\ToUpperCase;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToUpperCase::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class ToUpperCaseTest extends TestCase
{
    #[Test]
    #[TestDox('__toString() describes its behaviour')]
    public function toStringTest(): void
    {
        self::assertSame(
            'Convert any string to upper case.',
            (string) (new ToUpperCase())
        );
    }

    #[Test]
    #[TestDox('__toPHP() returns equivalent object as evaluable string')]
    public function toPHPTest(): void
    {
        $sut = new ToUpperCase();

        $actual = eval('return ' . $sut->__toPHP() . ';');

        self::assertEquals($sut, $actual);
    }

    #[Test]
    #[TestDox('It invalidates values that are not strings')]
    public function itInvalidatesNonStringValues(): void
    {
        $nonStringValue = 5;
        $expected = Result::invalid(
            $nonStringValue,
            new MessageSet(null, new Message(
                'ToUpperCase Filter expects a string, %s passed instead',
                [gettype($nonStringValue)]
            ))
        );

        self::assertEquals($expected, (new ToUpperCase())->filter($nonStringValue));
    }

    #[Test]
    #[TestDox('It filters strings to upper case')]
    #[DataProvider('provideStringsToFilter')]
    public function itFiltersStrings(Result $expected, string $value): void
    {
        self::assertEquals($expected, (new ToUpperCase())->filter($value));
    }

    /**
     * @return array<array{0:string, 1:Result}>
     */
    public static function provideStringsToFilter(): array
    {
        return [
            'camelCase to CamelCase' => [
                Result::noResult('CAMELCASE'),
                'camelCase',
            ],
            'kebab-case to KebabCase' => [
                Result::noResult('KEBAB-CASE'),
                'kebab-case',
            ],
            'snake_case to SnakeCase' => [
                Result::noResult('SNAKE-CASE'),
                'snake-case',
            ],
            'snake_____case to SnakeCase' => [
                Result::noResult('SNAKE_____CASE'),
                'snake_____case',
            ],
            'plain text to PlainText' => [
                Result::noResult('PLAIN TEXT'),
                'plain text',
            ],
            'plain 1text to Plain1Text' => [
                Result::noResult('PLAIN 1TEXT'),
                'plain 1text',
            ],
            'plain 1 text to Plain1Text' => [
                Result::noResult('PLAIN 1 TEXT'),
                'plain 1 text',
            ],
            'sTuPiD-_-cAsE to STuPiDCAsE' => [
                Result::noResult('STUPID-_-CASE'),
                'sTuPiD-_-cAsE',
            ],
        ];
    }
}
