<?php

declare(strict_types=1);

namespace OpenAPI\Filter;

use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Filter\HTTPParameters
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class HTTPParametersTest extends TestCase
{
    public function dataSetsToFilter(): array
    {
        return [
            [
                ['id' => '1'],
                Result::invalid(['id' => '1'],
                    new MessageSet(
                        null,
                        new Message('HTTPParameters expects string value, %s passed instead', ['array'])
                    )),
            ],
            [
                'id=1',
                Result::valid(['id' => '1']),
            ],
            [
                'id=1&name=Ben',
                Result::valid(['id' => '1', 'name' => 'Ben']),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToFilter
     */
    public function filterTest(mixed $input, Result $expected): void
    {
        $sut = new HTTPParameters();

        $actual = $sut->filter($input);

        self::assertEquals($expected, $actual);
    }
}
