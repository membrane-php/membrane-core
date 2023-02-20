<?php

declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Not;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Not
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class NotTest extends TestCase
{
    /** @test */
    public function toStringTest(): void
    {
        $expected = 'must satisfy the opposite of the following: "inverted condition"';
        $invertedValidator = self::createMock(Validator::class);
        $sut = new Not($invertedValidator);

        $invertedValidator->expects($this->once())
            ->method('__toString')
            ->willReturn('inverted condition');

        $actual = $sut->__toString();

        self::assertSame($expected, $actual);
    }

    public static function toPHPProvider(): array
    {
        return [
            'fails' => [new Fails()],
            'indifferent' => [new Indifferent()],
            'passes' => [new Passes()],
        ];
    }

    /**
     * @test
     * @dataProvider toPHPProvider
     */
    public function toPHPTest(Validator $invertedValidator): void
    {
        $sut = new Not($invertedValidator);

        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    public static function dataSetsToValidate(): array
    {
        return [
            'inverted invalid results will be valid' => [
                'a',
                new Fails(),
                Result::valid('a'),
            ],
            'inverted noResult results will stay noResult' => [
                'b',
                new Indifferent(),
                Result::noResult('b'),
            ],
            'inverted valid results will be invalid' => [
                'c',
                new Passes(),
                Result::invalid(
                    'c',
                    new MessageSet(null, new Message('Inverted validator: %s returned valid', [Passes::class]))
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function notInvertsInnerValidator(mixed $input, Validator $invertedValidator, Result $expected): void
    {
        $sut = new Not($invertedValidator);

        $actual = $sut->validate($input);

        self::assertEquals($expected, $actual);
    }
}
