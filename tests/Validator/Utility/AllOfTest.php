<?php

declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use Membrane\Validator\Utility\AllOf;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\AllOf
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class AllOfTest extends TestCase
{
    public function dataSetsToConvertToString(): array
    {
        $validator = self::createMock(Validator::class);
        $validator->method('__toString')
            ->willReturn('condition');

        return [
            'no validators' => [
                [],
                '',
            ],
            'single validator' => [
                [$validator],
                <<<END
                must satisfy all of the following:
                \t- condition.
                END,
            ],
            'multiple validators' => [
                [$validator, $validator, $validator],
                <<<END
                must satisfy all of the following:
                \t- condition.
                \t- condition.
                \t- condition.
                END,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
    public function toStringtest(array $chain, string $expected): void
    {
        $sut = new AllOf(...$chain);

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public function dataSetsToConvertToPHPString(): array
    {
        return [
            'no validators' => [new AllOf()],
            '1 validator' => [new AllOf(new Passes())],
            '3 validators' => [new AllOf(new Fails(), new Indifferent(), new Passes())],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToPHPString
     */
    public function toPHPTest(AllOf $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    /**
     * @test
     */
    public function noValidatorsReturnsNoResults(): void
    {
        $input = 'this can be anything';
        $expected = Result::noResult($input);
        $allOf = new AllOf();

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function singlePassReturnsValid(): void
    {
        $input = 'this can be anything';
        $expected = Result::valid($input);
        $allOf = new AllOf(new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function singleFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage));
        $allOf = new AllOf(new Fails());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function twoPassesReturnsValid(): void
    {
        $input = 'this can be anything';
        $expected = Result::valid($input);
        $allOf = new AllOf(new Passes(), new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function twoFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage, $expectedFailsMessage));
        $allOf = new AllOf(new Fails(), new Fails());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function failAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage));
        $allOf = new AllOf(new Fails(), new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
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
