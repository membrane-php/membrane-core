<?php
declare(strict_types=1);

namespace Filter\CreateObject;

use Membrane\Filter\CreateObject\FromArray;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\CreateObject\FromArray
 * @uses \Membrane\Result\Result
 * @uses \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Message
 */
class FromArrayTest extends TestCase
{
    /**
     * @test
     */
    public function NoFromArrayMethodReturnsInvalid()
    {
        $classWithoutMethod = new class {};
        $fromArray = new FromArray(get_class($classWithoutMethod));
        $expectedResult = Result::INVALID;
        $expectedMessage = 'Class (%s) doesnt have a fromArray method defined';
        $expectedVars = [get_class($classWithoutMethod)];

        $result = $fromArray->filter(['a' => 1, 'b' => 2]);

        self::assertEquals($expectedMessage, $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedVars, $result->messageSets[0]?->messages[0]?->vars);
        self::assertEquals($expectedResult, $result->result);
    }

    /**
     * @test
     */
    public function IncorrectFilterInputReturnsInvalid()
    {
        $classWithMethod = new class ()
        {
            public static function fromArray(array $values) : string
            {
                return 'this method should not be called';
            }
        };
        $fromArray = new FromArray(get_class($classWithMethod));
        $expectedResult = Result::INVALID;
        $expectedMessage = 'Value passed to FromArray filter must be an array, %s passed instead';
        $expectedVars = ['string'];

        $result = $fromArray->filter('this is not an array');

        self::assertEquals($expectedMessage, $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals($expectedVars, $result->messageSets[0]?->messages[0]?->vars);
        self::assertEquals($expectedResult, $result->result);
    }

    /**
     * @test
     */
    public function CorrectFilterInputReturnsResult()
    {
        $emptyArray = [];
        $classWithMethod = new class($emptyArray)
        {
            function __construct($emptyArray){
                $this->array = $emptyArray;
        }
            public static function fromArray(array $values) : self
            {
                return new self($values);
            }
        };
        $fromArray = new FromArray(get_class($classWithMethod));
        $input = ['a', 'b', 'c'];
        $expectedValue = ['a', 'b', 'c'];
        $expectedResult = Result::NO_RESULT;

        $result = $fromArray->filter($input);

        self::assertEquals($expectedValue, $result->value->array);
        self::assertEquals($expectedResult, $result->result);
    }
}