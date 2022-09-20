<?php
declare(strict_types=1);

namespace Result;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Result\Result
 * @covers \Membrane\Result\MessageSet
 * @covers \Membrane\Result\Message
 */
class ResultTest extends TestCase
{
    /**
     * @test
     */
    public function ValidConstructorReturnsValid(): void
    {
        $input = 'arbitrary value';
        $expected = new Result($input, Result::VALID);

        $result = Result::valid($input);

        self::assertEquals($expected, $result);
        self::assertTrue($result->isValid());
    }

    /**
     * @test
     */
    public function inValidConstructorReturnsInvalid(): void
    {
        $inputValue = 'arbitrary value';
        $inputMessageSet = new MessageSet(null, new Message('arbitrary message', []));
        $expected = new Result($inputValue, Result::INVALID, $inputMessageSet);

        $result = Result::invalid($inputValue, $inputMessageSet);

        self::assertEquals($expected, $result);
        self::assertFalse($result->isValid());
    }

    /**
     * @test
     */
    public function NoResultConstructorReturnsNoResult(): void
    {
        $input = 'arbitrary value';
        $expected = new Result($input, Result::NO_RESULT);

        $result = Result::noResult($input);

        self::assertEquals($expected, $result);
        self::assertTrue($result->isValid());
    }

    /**
     * @test
     */
    public function MergeTwoValidsReturnsValid(): void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstResult = Result::valid($firstInputValue);
        $secondResult = Result::valid($secondInputValue);
        $expected = Result::valid($secondInputValue);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($expected, $mergedResult);
    }

    /**
     * @test
     */
    public function MergeNoResultAndValidReturnsValid(): void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstResult = Result::noResult($firstInputValue);
        $secondResult = Result::valid($secondInputValue);
        $expected = Result::valid($secondInputValue);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($expected, $mergedResult);
    }

    /**
     * @test
     */
    public function MergeInvalidAndValidReturnsInvalid(): void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessageSet = new MessageSet(null, new Message('a message', []));
        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::valid($secondInputValue);
        $expected = Result::invalid($secondInputValue, $firstMessageSet);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($expected, $mergedResult);
    }

    /**
     * @test
     */
    public function MergeTwoInvalidsReturnsInvalid(): void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessageSet = new MessageSet(null, new Message('a message', []));
        $secondMessageSet = new MessageSet(null, new Message('another message', []));
        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::invalid($secondInputValue, $secondMessageSet);
        $expected = Result::invalid($secondInputValue, $firstMessageSet, $secondMessageSet);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($expected, $mergedResult);
    }

    /**
     * @test
     */
    public function MergeNoResultAndInvalidReturnsInvalid(): void
    {
        $firstValue = 'a value';
        $secondValue = 'another value';
        $firstMessageSet = new MessageSet(null, new Message('a message', []));
        $firstResult = Result::invalid($firstValue, $firstMessageSet);
        $secondResult = Result::noResult($secondValue);
        $expected = Result::invalid($secondValue, $firstMessageSet);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($expected, $mergedResult);
    }
}
