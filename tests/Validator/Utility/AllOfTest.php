<?php
declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\AllOf;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\AllOf
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class AllOfTest extends TestCase
{
    /**
     * @test
     */
    public function NoValidatorsReturnsNoResults(): void
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
    public function SinglePassReturnsValid(): void
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
    public function SingleFailsReturnsInvalid(): void
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
    public function TwoPassesReturnsValid(): void
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
    public function TwoFailsReturnsInvalid(): void
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
    public function FailAndPassesReturnsInvalid(): void
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
    public function MultipleFailsAndPassesReturnsInvalid(): void
    {
        $input = 'this can be anything';
        $expectedFailsMessage = new Message('I always fail', []);
        $expected = Result::invalid($input, new MessageSet(null, $expectedFailsMessage, $expectedFailsMessage));
        $allOf = new AllOf(new Fails(), new Passes(), new Fails(), new Passes());

        $result = $allOf->validate($input);

        self::assertEquals($expected, $result);
    }
}
