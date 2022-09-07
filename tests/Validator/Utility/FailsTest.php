<?php
declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Result;
use Membrane\Validator\Utility\Fails;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Fails
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Message
 */

class FailsTest extends TestCase
{
    public function dataSets(): array
    {
        /**
         * @return array
         */
        return [
            [1, Result::INVALID],
            [1.1, Result::INVALID],
            ['one', Result::INVALID],
            [true, Result::INVALID],
            [null, Result::INVALID],
        ];
    }

    /**
     * @test
     * @dataProvider dataSets
     */
    public function FailsAlwaysReturnsInvalid(mixed $input, int $expected): void
    {
        $fail = new Fails;

        $result = $fail->validate($input);

        self::assertEquals('I always fail', $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expected, $result->result);
    }
}