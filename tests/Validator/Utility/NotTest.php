<?php

declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Not;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Not
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class NotTest extends TestCase
{
    /**
     * @test
     */
    public function notFailsAlwaysReturnsValid(): void
    {
        $input = 'any input will not fail';
        $expected = Result::valid($input);
        $notFail = new Not(new Fails());

        $result = $notFail->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function notPassesAlwaysReturnsInvalid(): void
    {
        $input = 'any input will not pass';
        $expected = Result::invalid($input, new MessageSet(null, new Message('Inner validator was valid', [])));
        $notPasses = new Not(new Passes());

        $result = $notPasses->validate('any input will not pass');

        self::assertEquals($expected, $result);
    }
}
