<?php

declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\Fails;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class FailsTest extends TestCase
{
    public function dataSets(): array
    {
        return [[1], [1.1], ['one'], [true], [null],];
    }

    /**
     * @test
     * @dataProvider dataSets
     */
    public function failsAlwaysReturnsInvalid(mixed $input): void
    {
        $expected = Result::invalid($input, new MessageSet(null, new Message('I always fail', [])));
        $fail = new Fails();

        $result = $fail->validate($input);

        self::assertEquals($expected, $result);
    }
}
