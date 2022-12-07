<?php

declare(strict_types=1);

namespace Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Type\IsNumber;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Type\IsNumber
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class IsNumberTest extends TestCase
{

    public function dataSetsToValidate(): array
    {
        $class = new class {
        };

        return [
            'validates integer' => [
                1,
                Result::valid(1),
            ],
            'validates float' => [
                2.3,
                Result::valid(2.3),
            ],
            'invalidates integer string' => [
                '4',
                Result::invalid(
                    '4',
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['string']))
                ),
            ],
            'invalidates float string' => [
                '5.6',
                Result::invalid(
                    '5.6',
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['string']))
                ),
            ],
            'invalidates non-numeric string' => [
                'six',
                Result::invalid(
                    'six',
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['string']))
                ),
            ],
            'invalidates bool' => [
                true,
                Result::invalid(
                    true,
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['boolean']))
                ),
            ],
            'invalidates null' => [
                null,
                Result::invalid(
                    null,
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['NULL']))
                ),
            ],
            'invalidates array' => [
                [1, 2, 3],
                Result::invalid([1, 2, 3],
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['array']))),
            ],
            'invalidates object' => [
                $class,
                Result::invalid(
                    $class,
                    new MessageSet(null, new Message('Value must be a number, %s passed', ['object']))
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(mixed $value, Result $expected): void
    {
        $sut = new IsNumber();

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }

}
