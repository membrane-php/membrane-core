<?php

declare(strict_types=1);

namespace Validator\Logical;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Logical\OneOf;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
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
    public function DataSetsThatReturnNoResult(): array
    {
        return [
            [[]],
            [[new Indifferent()]],
            [[new Indifferent(), new Indifferent(), new Indifferent()]]
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatReturnNoResult
     */
    public function NoResultsReturnsNoResult(array $chain): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(...$chain);
        $expected = Result::noResult($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    public function DataSetsThatReturnInvalid(): array
    {
        $expectedMessage = new Message('I always fail', []);
        return [
            [[new Fails()], new MessageSet(null, $expectedMessage)],
            [[new Fails(), new Fails(), new Fails()], new MessageSet(null, $expectedMessage, $expectedMessage, $expectedMessage)],
            [[new Indifferent(), new Fails(), new Indifferent(), new Indifferent()], new MessageSet(null, $expectedMessage)]
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatReturnInvalid
     */
    public function SingleFailsReturnsInvalid(array $chain, MessageSet $expectedMessageSet): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(...$chain);
        $expected = Result::invalid($input, $expectedMessageSet);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    public function DataSetsThatReturnValid(): array
    {
        return [
            [[new Passes()]],
            [[new Indifferent(), new Passes()]],
            [[new Fails(), new Passes()]],
            [[new Fails(), new Indifferent(), new Passes()]],
            [[new Fails(), new Indifferent(), new Passes(), new Fails(), new Fails(), new Indifferent()]]
        ];
    }

    /**
     * @test
     * @dataProvider DataSetsThatReturnValid
     */
    public function AnyValidResultsReturnsValid(array $chain): void
    {
        $input = 'this can be anything';
        $oneOf = new OneOf(...$chain);
        $expected = Result::valid($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }
}
