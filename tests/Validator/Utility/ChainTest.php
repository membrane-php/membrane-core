<?php
declare(strict_types=1);

namespace Validator\Utility;

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
 * @uses \Membrane\Result\Message
 * @uses \Membrane\Result\MessageSet
 */
class ChainTest extends TestCase
{
    /**
     * @test
     */
    public function TwoPassesReturnsValid(): void
    {
        $input = 'this can be anything';
        $expected = Result::VALID;
        $chain = new Chain(new Passes, new Passes);

        $result = $chain->validate($input);

        self::assertEquals($expected, $result->result);
    }

    /**
     * @test
     */
    public function TwoFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expected = Result::INVALID;
        $chain = new Chain(new Fails, new Fails);

        $result = $chain->validate($input);

        self::assertCount(1, $result->messageSets);
        self::assertCount(2, $result->messageSets[0]->messages);
        self::assertEquals($expected, $result->result);
    }

    /**
     * @test
     */
    public function FailAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expected = Result::INVALID;
        $chain = new Chain(new Fails, new Passes);

        $result = $chain->validate($input);

        self::assertCount(1, $result->messageSets);
        self::assertCount(1, $result->messageSets[0]->messages);
        self::assertEquals($expected, $result->result);
    }

    /**
     * @test
     */
    public function TwoFailsAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expected = Result::INVALID;
        $chain = new Chain(new Fails, new Passes, new Fails);

        $result = $chain->validate($input);

        self::assertCount(1, $result->messageSets);
        self::assertCount(2, $result->messageSets[0]->messages);
        self::assertEquals($expected, $result->result);
    }
}