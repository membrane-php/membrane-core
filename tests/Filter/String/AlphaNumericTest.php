<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\String;

use Membrane\Filter\String\AlphaNumeric;
use Membrane\Result\{Message, MessageSet, Result};
use PHPUnit\Framework\Attributes\{CoversClass, DataProvider, Test, TestDox, UsesClass};
use PHPUnit\Framework\TestCase;

#[CoversClass(AlphaNumeric::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class AlphaNumericTest extends TestCase
{
    public AlphaNumeric $sut;

    protected function setUp(): void
    {
        $this->sut = new AlphaNumeric();
    }

    #[Test, TestDox('toString method will describe what the Filter does')]
    public function toStringTest(): void
    {
        $expected = 'Remove all non-alphanumeric characters';

        self::assertSame($expected, (string)$this->sut);
    }

    #[Test, TestDox('__toPHP() will return evaluable PHP code as a string')]
    public function toPHPTest(): void
    {
        $expected = new AlphaNumeric();

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
                new Message('AlphaNumeric Filter expects a string value, %s passed instead', [gettype($nonStringValue)])
            )
        );

        self::assertEquals($expected, $this->sut->filter($nonStringValue));
    }

    public static function provideStringsToFilter(): array
    {
        return [
            'abra-kadabra to abrakadabra' => [
                'abra-kadabra',
                Result::noResult('abrakadabra'),
            ],
            'H*o-c+u%s P^0c"u!s to HocusP0cus' => [
                'H*o-c+u%s P^0c"u!s',
                Result::noResult('HocusP0cus'),
            ],
        ];
    }

    #[Test, TestDox('It filters strings to contain only alphanumeric characters')]
    #[DataProvider('provideStringsToFilter')]
    public function filtersStringToAlphaNumericString(string $value, Result $expected): void
    {
        self::assertEquals($expected, $this->sut->filter($value));
    }
}
