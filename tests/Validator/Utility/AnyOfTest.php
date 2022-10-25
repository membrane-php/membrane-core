<?php

declare(strict_types=1);

namespace Validator\Utility;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\AnyOf;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Indifferent;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Utility\AnyOf
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Indifferent
 * @uses   \Membrane\Validator\Utility\Passes
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class AnyOfTest extends TestCase
{
    public function dataSetsThatReturnNoResult(): array
    {
        return [
            [[]],
            [[new Indifferent()]],
            [[new Indifferent(), new Indifferent(), new Indifferent()]],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatReturnNoResult
     */
    public function noResultsReturnsNoResult(array $chain): void
    {
        $input = 'this can be anything';
        $oneOf = new AnyOf(...$chain);
        $expected = Result::noResult($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatReturnInvalid(): array
    {
        $expectedMessage = new Message('I always fail', []);
        return [
            [
                [new Fails()],
                new MessageSet(null, $expectedMessage),
            ],
            [
                [new Fails(), new Fails(), new Fails()],
                new MessageSet(null, $expectedMessage, $expectedMessage, $expectedMessage),
            ],
            [
                [new Indifferent(), new Fails(), new Indifferent(), new Indifferent()],
                new MessageSet(null, $expectedMessage),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatReturnInvalid
     */
    public function singleFailsReturnsInvalid(array $chain, MessageSet $expectedMessageSet): void
    {
        $input = 'this can be anything';
        $oneOf = new AnyOf(...$chain);
        $expected = Result::invalid($input, $expectedMessageSet);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }

    public function dataSetsThatReturnValid(): array
    {
        return [
            [[new Passes()]],
            [[new Indifferent(), new Passes()]],
            [[new Fails(), new Passes()]],
            [[new Fails(), new Indifferent(), new Passes()]],
            [[new Fails(), new Indifferent(), new Passes(), new Fails(), new Fails(), new Indifferent()]],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatReturnValid
     */
    public function anyValidResultsReturnsValid(array $chain): void
    {
        $input = 'this can be anything';
        $oneOf = new AnyOf(...$chain);
        $expected = Result::valid($input);

        $result = $oneOf->validate($input);

        self::assertEquals($expected, $result);
    }
}
