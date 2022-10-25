<?php

declare(strict_types=1);

namespace Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Collection\Contained;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Collection\Contained
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 */
class ContainedTest extends TestCase
{
    public function dataSetsToValidate(): array
    {
        return [
            'value contained in array' => [
                true,
                [true, false],
                Result::valid(true),
            ],
            'value not contained in array' => [
                'Where am I?',
                ['Not', 'in', 'here'],
                Result::invalid(
                    'Where am I?',
                    new MessageSet(
                        null,
                        new Message('Contained validator did not find value within array', [['Not', 'in', 'here']])
                    )
                ),
            ],
            'value of different type than array items' => [
                1,
                ['1', '2', '3'],
                Result::invalid(
                    1,
                    new MessageSet(
                        null,
                        new Message('Contained validator did not find value within array', [['1', '2', '3']])
                    )
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToValidate
     */
    public function validateTest(mixed $value, array $enum, Result $expected): void
    {
        $sut = new Contained($enum);

        $actual = $sut->validate($value);

        self::assertEquals($expected, $actual);
    }
}
