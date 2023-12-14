<?php

declare(strict_types=1);

namespace Membrane\Tests\Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\AllOf;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllOf::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Indifferent::class)]
#[UsesClass(Passes::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class AllOfTest extends TestCase
{
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
                must satisfy all of the following:
                \t- will return valid.
                END,
            ],
            'multiple validators' => [
                [new Fails(), new Indifferent(), new Passes()],
                <<<END
                must satisfy all of the following:
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
        $sut = new AllOf(...$chain);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no validators' => [new AllOf()],
            '1 validator' => [new AllOf(new Passes())],
            '3 validators' => [new AllOf(new Fails(), new Indifferent(), new Passes())],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(AllOf $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function noValidatorsReturnsNoResults(): void
    {
        $input = 'this can be anything';
        $expected = Result::noResult($input);
        $allOf = new AllOf();

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function singlePassReturnsValid(): void
    {
        $input = 'this can be anything';
        $expected = Result::valid($input);
        $allOf = new AllOf(new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function singleFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage));
        $allOf = new AllOf(new Fails());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function twoPassesReturnsValid(): void
    {
        $input = 'this can be anything';
        $expected = Result::valid($input);
        $allOf = new AllOf(new Passes(), new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function twoFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage, $expectedFailsMessage));
        $allOf = new AllOf(new Fails(), new Fails());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function failAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage));
        $allOf = new AllOf(new Fails(), new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function multipleFailsAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage, $expectedFailsMessage));
        $allOf = new AllOf(new Fails(), new Passes(), new Fails(), new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }
}
