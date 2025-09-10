<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\String;

use Membrane\Filter\String\ToPascalCase;
use Membrane\Result\{Message, MessageSet, Result};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;

#[CoversClass(ToPascalCase::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class ToPascalCaseTest extends TestCase
{
    public ToPascalCase $sut;

    public function setUp(): void
    {
        $this->sut = new ToPascalCase();
    }

    #[Test, TestDox('toString method will describe what the Filter does')]
    public function toStringTest(): void
    {
        $expected = 'Convert camelCase, kebab-case, snake-case, or plain text with whitespaces into PascalCase';

        self::assertSame($expected, (string)$this->sut);
    }

    #[Test, TestDox('__toPHP() will return evaluable PHP code as a string')]
    public function toPHPTest(): void
    {
        $expected = new ToPascalCase();

        $actual = eval('return ' . $this->sut->__toPHP() . ';');

        self::assertEquals($expected, $actual);
    }

    #[Test, TestDox('It will return an invalid Result if it filters values that are not strings')]
    public function returnsInvalidResultForNonStringValues(): void
    {
        $nonStringValue = 5;
        $expected = Result::invalid(
            $nonStringValue,
            new MessageSet(
                null,
                new Message('ToPascalCase Filter expects a string value, %s passed instead', [gettype($nonStringValue)])
            )
        );

        self::assertEquals($expected, $this->sut->filter($nonStringValue));
    }

    public static function provideStringsToFilter(): array
    {
        return [
            'camelCase to CamelCase' => [
                'camelCase',
                Result::noResult('CamelCase'),
            ],
            'kebab-case to KebabCase' => [
                'kebab-case',
                Result::noResult('KebabCase'),
            ],
            'snake_case to SnakeCase' => [
                'snake_case',
                Result::noResult('SnakeCase'),
            ],
            'snake_____case to SnakeCase' => [
                'snake_____case',
                Result::noResult('SnakeCase'),
            ],
            'plain text to PlainText' => [
                'plain text',
                Result::noResult('PlainText'),
            ],
            'plain 1text to Plain1Text' => [
                'plain 1text',
                Result::noResult('Plain1text'),
            ],
            'plain 1 text to Plain1Text' => [
                'plain 1 text',
                Result::noResult('Plain1Text'),
            ],
            'sTuPiD-_-cAsE to STuPiDCAsE' => [
                'sTuPiD-_-cAsE',
                Result::noResult('STuPiDCAsE'),
            ],
        ];
    }

    #[Test, TestDox('It filters strings to PascalCase')]
    #[DataProvider('provideStringsToFilter')]
    public function filtersStringsToPascalCase(string $value, Result $expected): void
    {
        self::assertEquals($expected, $this->sut->filter($value));
    }
}
