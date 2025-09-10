<?php

declare(strict_types=1);

namespace Filter\String;

use Membrane\Filter\String\ToKebabCase;
use Membrane\Result\{Message, MessageSet, Result};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;

#[CoversClass(ToKebabCase::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class ToKebabCaseTest extends TestCase
{
    #[Test]
    #[TestDox('toString() returns a description of its behaviour')]
    public function itCaststoString(): void
    {
        self::assertSame(
            'Convert text to kebab-case',
            (new ToKebabCase())->__toString()
        );
    }

    #[Test]
    #[TestDox('__toPHP() returns a string of evaluable PHP')]
    public function itCaststoPHP(): void
    {
        $sut = new ToKebabCase();

        self::assertEquals($sut, eval('return ' . $sut->__toPHP() . ';'));
    }

    #[Test]
    #[TestDox('It will return an invalid Result if it filters values that are not strings')]
    public function returnsInvalidResultForNonStringValues(): void
    {
        $value = 5;
        $expected = Result::invalid($value, new MessageSet(null, new Message(
            'Expected string value, received %s',
            [gettype($value)],
        )));

        self::assertEquals($expected, (new ToKebabCase())->filter($value));
    }

    #[Test]
    #[TestDox('It filters strings to PascalCase')]
    #[DataProvider('provideStringsToFilter')]
    public function filtersStringsToKebabCase(string $value, Result $expected): void
    {
        self::assertEquals($expected, (new ToKebabCase())->filter($value));
    }

    public static function provideStringsToFilter(): array
    {
        return [
            '"Hello, World!"' => [
                '"Hello, World!"',
                Result::noResult('hello-world'),
            ],
            'camelCase' => [
                'camelCase',
                Result::noResult('camelcase'),
            ],
            'kebab-case' => [
                'kebab-case',
                Result::noResult('kebab-case'),
            ],
            'kebabber------case' => [
                'kebabber------case',
                Result::noResult('kebabber-case'),
            ],
            'snake_case' => [
                'snake_case',
                Result::noResult('snake-case'),
            ],
            'snakiest_____case' => [
                'snakiest_____case',
                Result::noResult('snakiest-case'),
            ],
            'pets/{id}' => [
                'pets/{id}',
                Result::noResult('pets-id'),
            ],
            'http://petstore.swagger.io/{version}/pets/{id}' => [
                'http://petstore.swagger.io/{version}/pets/{id}',
                Result::noResult('http-petstore-swagger-io-version-pets-id'),
            ],
            'plain text' => [
                'plain text',
                Result::noResult('plain-text'),
            ],
            'plain 1text' => [
                'plain 1text',
                Result::noResult('plain-1text'),
            ],
            'plain 1 text' => [
                'plain 1 text',
                Result::noResult('plain-1-text'),
            ],
            'sTuPiD-_-cAsE' => [
                'sTuPiD-_-cAsE',
                Result::noResult('stupid-case'),
            ],
        ];
    }


}
