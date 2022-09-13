<?php

declare(strict_types=1);

namespace Validator\Logical;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Logical\OneOf;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Logical\OneOf
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class OneOfTest extends TestCase
{
    /**
     * @test
     */
    public function NoValidatorReturnsNoResult(): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf();
        $expected = Result::noResult($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function SinglePassReturnsValid(): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(new Passes);
        $expected = Result::valid($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function MultiplePassesReturnsValid(): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(new Passes, new Passes, new Passes);
        $expected = Result::valid($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function SingleFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(new Fails);
        $expectedMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage));

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function MultipleFailsReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(new Fails, new Fails, new Fails);
        $expectedMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedMessage, $expectedMessage, $expectedMessage));

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function PassAndFailsReturnsValid(): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(new Fails, new Passes);
        $expected = Result::valid($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function MultiplePassAndFailsReturnsValid(): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(new Fails, new Passes, new Fails, new Passes);
        $expected = Result::valid($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }
}
