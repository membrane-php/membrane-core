<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\AnyOf;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnyOf::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class AnyOfTest extends TestCase
{
    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no validators' => [new AnyOf()],
            '1 validator' => [new AnyOf(new Passes())],
            '3 validators' => [new AnyOf(new Fails(), new Indifferent(), new Passes())],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(AnyOf $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToConvertToString(): array
    {
        return [
            'no validators' => [
                [],
                '',
            ],
            'single validator' => [
                [new Passes()],
                <<<END
                must satisfy at least one of the following:
                \t- will return valid.
                END,
            ],
            'multiple validators' => [
                [new Fails(), new Indifferent(), new Passes()],
                <<<END
                must satisfy at least one of the following:
                \t- will return invalid.
                \t- will return valid.
                END,
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringtest(array $chain, string $expected): void
    {
        $sut = new AnyOf(...$chain);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToValidate(): array
    {
        return [
            'no validators' => [
                'a',
                [],
                Result::noResult('a'),
            ],
            'single invalid result' => [
                'b',
                [new Fails()],
                Result::invalid('b', new MessageSet(null, new Message('I always fail', []))),
            ],
            'single noResult' => [
                'c',
                [new Indifferent()],
                Result::noResult('c'),
            ],
            'single valid result' => [
                'd',
                [new Passes()],
                Result::valid('d'),
            ],
            'multiple invalid results' => [
                'e',
                [new Fails(), new Fails(), new Fails()],
                Result::invalid(
                    'e',
                    new MessageSet(
                        null,
                        new Message('I always fail', []),
                        new Message('I always fail', []),
                        new Message('I always fail', [])
                    )
                ),
            ],
            'multiple noResults' => [
                'f',
                [new Indifferent(), new Indifferent(), new Indifferent()],
                Result::noResult('f'),
            ],
            'multiple valid results' => [
                'g',
                [new Passes(), new Passes(), new Passes()],
                Result::valid('g'),
            ],
            'mix of invalid and noResult' => [
                'h',
                [new Indifferent(), new Fails(), new Indifferent()],
                Result::invalid('h', new MessageSet(null, new Message('I always fail', []))),
            ],
            'mix of invalid and valid results' => [
                'i',
                [new Fails(), new Passes(), new Fails()],
                Result::valid('i'),
            ],
            'mix of valid and noResult' => [
                'j',
                [new Indifferent(), new Passes(), new Indifferent()],
                Result::valid('j'),
            ],
            'mix of invalid, valid and noResult' => [
                'k',
                [new Fails(), new Indifferent(), new Passes()],
                Result::valid('k'),
            ],
        ];
    }

    #[DataProvider('dataSetsToValidate')]
    #[Test]
    public function validateTest(mixed $value, array $chain, Result $expected): void
    {
        $sut = new AnyOf(...$chain);

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }
}
