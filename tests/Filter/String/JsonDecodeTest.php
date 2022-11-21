<?php

declare(strict_types=1);

namespace Filter\String;

use Membrane\Filter\String\JsonDecode;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\String\JsonDecode
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class JsonDecodeTest extends TestCase
{
    public function dataSetsToFilter(): array
    {
        return [
            'value passed is not a string' => [
                5,
                Result::invalid(
                    5,
                    new MessageSet(
                        null,
                        new Message('JsonDecode Filter expects a string value, %s passed instead', ['integer'])
                    )
                ),
            ],
            'value passed causes syntax error' => [
                '"id": 1, "name": "Spike", "type": "dog"}',
                Result::invalid(
                    null,
                    new MessageSet(
                        null,
                        new Message('Syntax error occurred', [])
                    )
                ),
            ],
            'value passed is correct json format' => [
                '{"id": 1, "name": "Spike", "type": "dog"}',
                Result::valid(['id' => '1', 'name' => 'Spike', 'type' => 'dog']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToFilter
     */
    public function filterTest(mixed $value, Result $expected): void
    {
        $sut = new JsonDecode();

        $actual = $sut->filter($value);

        self::assertEquals($expected, $actual);
    }

}
