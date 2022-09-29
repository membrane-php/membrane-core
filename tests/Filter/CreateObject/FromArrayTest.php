<?php

declare(strict_types=1);

namespace Filter\CreateObject;

use Membrane\Filter\CreateObject\FromArray;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\CreateObject\FromArray
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class FromArrayTest extends TestCase
{
    /**
     * @test
     */
    public function noFromArrayMethodReturnsInvalid(): void
    {
        $input = ['a' => 1, 'b' => 2];
        $classWithoutMethod = new class {
        };
        $fromArray = new FromArray(get_class($classWithoutMethod));
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message(
                    'Class (%s) doesnt have a fromArray method defined',
                    [get_class($classWithoutMethod)]
                )
            )
        );

        $result = $fromArray->filter($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function incorrectFilterInputReturnsInvalid(): void
    {
        $input = 'this is not an array';
        $classWithMethod = new class () {
            public static function fromArray(array $values): string
            {
                return 'this method should not be called';
            }
        };
        $fromArray = new FromArray(get_class($classWithMethod));
        $expected = Result::invalid(
            $input,
            new MessageSet(
                null,
                new Message(
                    'Value passed to FromArray filter must be an array, %s passed instead',
                    ['string']
                )
            )
        );

        $result = $fromArray->filter($input);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function correctFilterInputReturnsResult(): void
    {
        $input = ['a', 'b', 'c'];
        $classWithMethod = new class () {
            public static function fromArray(array $values): string
            {
                return implode('->', $values);
            }
        };
        $fromArray = new FromArray(get_class($classWithMethod));
        $expected = Result::noResult('a->b->c');

        $result = $fromArray->filter($input);

        self::assertEquals($expected, $result);
    }
}
