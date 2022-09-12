<?php
declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\Chain;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\Chain
 * @uses \Membrane\Validator\Utility\Fails
 * @uses \Membrane\Validator\Utility\Passes
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Message

 */
class ChainTest extends TestCase
{
    /**
     * @test
     */
    public function TwoPassesReturnsValid(): void
    {
        $input = 'this can be anything';
        $expected = Result::valid($input);
        $chain = new Chain(new Passes, new Passes);

        $result = $chain->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function TwoFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage, $expectedFailsMessage));
        $chain = new Chain(new Fails, new Fails);

        $result = $chain->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function FailAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage));
        $chain = new Chain(new Fails, new Passes);

        $result = $chain->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function MultipleFailsAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage, $expectedFailsMessage));
        $chain = new Chain(new Fails, new Passes, new Fails, new Passes);

        $result = $chain->validate($input);

        self::assertEquals($expected, $result);
    }
}