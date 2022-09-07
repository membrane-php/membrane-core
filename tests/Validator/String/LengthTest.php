<?php
declare(strict_types=1);

namespace Validator\String;

use Membrane\Result\Result;
use Membrane\Validator\String\Length;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\String\Length
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\Message
 * @uses \Membrane\Result\MessageSet
 */
class LengthTest extends TestCase
{
    /**
     * @return array
     */
    public function dataSetsThatPass(): array
    {
        return [
            ['', 0, 0, Result::VALID],
            ['', 0, null, Result::VALID],
            ['', 0, 5, Result::VALID],
            ['short', 0, 5, Result::VALID],
            ['longer string', 5, 100, Result::VALID],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function StringLengthWithinMinAndMaxReturnsValid(mixed $input, int $min, ?int $max, int $expected): void
    {
        $length = new Length($min, $max);

        $result = $length->validate($input);

        self::assertEquals($expected, $result->result);
    }

    /**
     * @return array
     */
    public function dataSetsThatFail(): array
    {
        return [
            ['', 1, 5, Result::INVALID, 'String is expected to be a minimum of %d characters', [1]],
            ['short', 6, 10, Result::INVALID, 'String is expected to be a minimum of %d characters', [6]],
            ['longer string.', 6, 10, Result::INVALID, 'String is expected to be a maximum of %d characters', [10]],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatFail
     */
    public function StringLengthOutsideMinOrMaxReturnInvalid(mixed $input, int $min, ?int $max, int $expected, $expectedMessage, $expectedVars): void
    {
        $length = new Length($min, $max);

        $result = $length->validate($input);

        self::assertCount(1, $result->messageSets);
        self::assertCount(1, $result->messageSets[0]->messages);
        self::assertEquals($expectedMessage, $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedVars, $result->messageSets[0]?->messages[0]?->vars);
        self::assertEquals($expected, $result->result);
    }
}