<?php

declare(strict_types=1);

namespace Membrane\Tests\Filter\String;

use Membrane\Filter\String\JsonDecode;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonDecode::class)]
#[UsesClass(Message::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Result::class)]
class JsonDecodeTest extends TestCase
{
    #[Test]
    public function toStringTest(): void
    {
        $expected = 'convert from json to a PHP value';
        $sut = new JsonDecode();

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toPHPTest(): void
    {
        $sut = new JsonDecode();

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToFilter(): array
    {
        return [
            'value is not a string' => [
                5,
                Result::invalid(
                    5,
                    new MessageSet(
                        null,
                        new Message('JsonDecode Filter expects a string value, %s passed instead', ['integer'])
                    )
                ),
            ],
            'value causes syntax error' => [
                '"id": 1, "name": "Spike", "type": "dog"}',
                Result::invalid(
                    null,
                    new MessageSet(
                        null,
                        new Message('Syntax error occurred', [])
                    )
                ),
            ],
            'value is correct json format of an object' => [
                '{"id": 1, "name": "Spike", "type": "dog"}',
                Result::valid(['id' => '1', 'name' => 'Spike', 'type' => 'dog']),
            ],
            'value is correct json format of a string' => [
                '"string"',
                Result::valid('string'),
            ],
            'value is correct json format of an array' => [
                '[ 1, 2, 3]',
                Result::valid([1, 2, 3]),
            ],
        ];
    }

    #[DataProvider('dataSetsToFilter')]
    #[Test]
    public function filterTest(mixed $value, Result $expected): void
    {
        $sut = new JsonDecode();

        $actual = $sut->filter($value);

        self::assertEquals($expected, $actual);
    }
}
