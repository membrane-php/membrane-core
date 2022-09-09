<?php

namespace Result;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use PHPUnit\Framework\TestCase;
use Membrane\Result\Result;

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
    public function ValidConstructorReturnsValid() : void
    {
        $inputValue = 'arbitrary value';
        $expectedResult = Result::VALID;

        $result = Result::valid($inputValue);

        self::assertEquals($inputValue, $result->value);
        self::assertEquals($expectedResult, $result->result);
        self::assertTrue($result->isValid());
    }

    /**
     * @test
     */
    public function inValidConstructorReturnsInvalid() : void
    {
        $inputValue = 'arbitrary value';
        $inputMessage = new Message('arbitrary message', []);
        $inputMessageSet = new MessageSet(null, $inputMessage);
        $expectedResult = Result::INVALID;

        $result = Result::invalid($inputValue, $inputMessageSet);

        self::assertEquals($inputValue, $result->value);
        self::assertEquals($inputMessage->message, $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedResult, $result->result);
        self::assertFalse($result->isValid());
    }

    /**
     * @test
     */
    public function NoResultConstructorReturnsNoResult() : void
    {
        $inputValue = 'arbitrary value';
        $expectedResult = Result::NO_RESULT;

        $result = Result::noResult($inputValue);

        self::assertEquals($inputValue, $result->value);
        self::assertEquals($expectedResult, $result->result);
        self::assertTrue($result->isValid());
    }

    public function dataSetsForMerges() {

    }

    /**
     * @test
     */
    public function MergeTwoValidsReturnsValid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $expectedResult = Result::VALID;

        $firstResult = Result::valid($firstInputValue);
        $secondResult = Result::valid($secondInputValue);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function MergeNoResultAndValidReturnsValid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $expectedResult = Result::VALID;

        $firstResult = Result::noResult($firstInputValue);
        $secondResult = Result::valid($secondInputValue);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function MergeInvalidAndValidReturnsInvalid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessage = new Message('a message', []);
        $firstMessageSet = new MessageSet(null, $firstMessage);
        $expectedResult = Result::INVALID;

        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::valid($secondInputValue);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($firstMessage->message, $mergedResult->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function MergeTwoInvalidsReturnsInvalid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessage = new Message('a message', []);
        $secondMessage = new Message('another message', []);
        $firstMessageSet = new MessageSet(null, $firstMessage);
        $secondMessageSet = new MessageSet(null, $secondMessage);
        $expectedResult = Result::INVALID;

        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::invalid($secondInputValue, $secondMessageSet);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($firstMessage->message, $mergedResult->messageSets[0]?->messages[0]?->message);
        self::assertEquals($secondMessage->message, $mergedResult->messageSets[1]?->messages[0]?->message);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function MergeNoResultAndInvalidReturnsInvalid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessage = new Message('a message', []);
        $firstMessageSet = new MessageSet(null, $firstMessage);
        $expectedResult = Result::INVALID;

        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::noResult($secondInputValue);

        $mergedResult = $firstResult->merge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($firstMessage->message, $mergedResult->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    public function FullMergeTwoValidsReturnsValid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $expectedResult = Result::VALID;

        $firstResult = Result::valid($firstInputValue);
        $secondResult = Result::valid($secondInputValue);

        $mergedResult = $firstResult->fullMerge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function FullMergeNoResultAndValidReturnsValid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $expectedResult = Result::VALID;

        $firstResult = Result::noResult($firstInputValue);
        $secondResult = Result::valid($secondInputValue);

        $mergedResult = $firstResult->fullMerge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function FullMergeInvalidAndValidReturnsInvalid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessage = new Message('a message', []);
        $firstMessageSet = new MessageSet(null, $firstMessage);
        $expectedResult = Result::INVALID;

        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::valid($secondInputValue);

        $mergedResult = $firstResult->fullMerge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($firstMessage->message, $mergedResult->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function FullMergeTwoInvalidsReturnsInvalid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessage = new Message('a message', []);
        $secondMessage = new Message('another message', []);
        $firstMessageSet = new MessageSet(null, $firstMessage);
        $secondMessageSet = new MessageSet(null, $secondMessage);
        $expectedResult = Result::INVALID;

        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::invalid($secondInputValue, $secondMessageSet);

        $mergedResult = $firstResult->fullMerge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($firstMessage->message, $mergedResult->messageSets[0]?->messages[0]?->message);
        self::assertEquals($secondMessage->message, $mergedResult->messageSets[0]?->messages[1]?->message);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

    /**
     * @test
     */
    public function FullMergeNoResultAndInvalidReturnsInvalid() : void
    {
        $firstInputValue = 'a value';
        $secondInputValue = 'another value';
        $firstMessage = new Message('a message', []);
        $firstMessageSet = new MessageSet(null, $firstMessage);
        $expectedResult = Result::INVALID;

        $firstResult = Result::invalid($firstInputValue, $firstMessageSet);
        $secondResult = Result::noResult($secondInputValue);

        $mergedResult = $firstResult->fullMerge($secondResult);

        self::assertEquals($secondInputValue, $mergedResult->value);
        self::assertEquals($firstMessage->message, $mergedResult->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedResult, $mergedResult->result);
    }

}