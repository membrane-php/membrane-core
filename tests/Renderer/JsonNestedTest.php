<?php

declare(strict_types=1);

namespace Renderer;

use Membrane\Renderer\JsonNested;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Renderer\JsonNested
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class JsonNestedTest extends TestCase
{
    public function dataSetsToRenderAsArrays(): array
    {
        $msg = fn($var) => new Message('%s', [$var]);

        return [
            'no messages' => [
                Result::valid(null),
                ['errors' => [], 'fields' => []],
            ],
            'empty messageSet' => [
                Result::invalid(null, new MessageSet(null)),
                ['errors' => [], 'fields' => []],
            ],
            'no fieldName' => [
                Result::invalid(null, new MessageSet(null, $msg(1))),
                ['errors' => ['1'], 'fields' => []],
            ],
            'empty string fieldName' => [
                Result::invalid(null, new MessageSet(new FieldName('', ''), $msg(1))),
                ['errors' => ['1'], 'fields' => []],
            ],
            'single one-level message' => [
                Result::invalid(null, new MessageSet(new FieldName('a'), $msg(1))),
                [
                    'errors' => [],
                    'fields' => [
                        'a' => ['errors' => ['1'], 'fields' => []],
                    ],
                ],

            ],
            'single two-level message' => [
                Result::invalid(null, new MessageSet(new FieldName('aa', 'a'), $msg(1))),
                [
                    'errors' => [],
                    'fields' => [
                        'a' => [
                            'errors' => [],
                            'fields' => [
                                'aa' => ['errors' => ['1'], 'fields' => []],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple messages' => [
                Result::invalid(null, new MessageSet(new FieldName('aa', 'a'), $msg(1), $msg(2))),
                [
                    'errors' => [],
                    'fields' => [
                        'a' => [
                            'errors' => [],
                            'fields' => [
                                'aa' => ['errors' => ['1', '2'], 'fields' => []],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple unrelated messageSets' => [
                Result::invalid(
                    null,
                    new MessageSet(new FieldName('aa', 'a'), $msg(1), $msg(2)),
                    new MessageSet(new FieldName('bbb', 'b', 'bb'), $msg(3)),
                ),
                [
                    'errors' => [],
                    'fields' => [
                        'a' => [
                            'errors' => [],
                            'fields' => [
                                'aa' => ['errors' => ['1', '2'], 'fields' => []],
                            ],
                        ],
                        'b' => [
                            'errors' => [],
                            'fields' => [
                                'bb' => [
                                    'errors' => [],
                                    'fields' => [
                                        'bbb' => ['errors' => ['3'], 'fields' => []],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'multiple related messageSets' => [
                Result::invalid(
                    null,
                    new MessageSet(new FieldName('a'), $msg(1)),
                    new MessageSet(new FieldName('aa', 'a'), $msg(2)),
                ),
                [
                    'errors' => [],
                    'fields' => [
                        'a' => [
                            'errors' => ['1'],
                            'fields' => ['aa' => ['errors' => ['2'], 'fields' => []]],
                        ],
                    ],
                ],
            ],
            'multiple related messageSets reversed' => [
                Result::invalid(
                    null,
                    new MessageSet(new FieldName('aa', 'a'), $msg(2)),
                    new MessageSet(new FieldName('a'), $msg(1)),
                ),
                [
                    'errors' => [],
                    'fields' => [
                        'a' => [
                            'errors' => ['1'],
                            'fields' => ['aa' => ['errors' => ['2'], 'fields' => []]],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToRenderAsArrays
     */
    public function toArrayTest(Result $result, array $expected): void
    {
        $sut = new JsonNested($result);

        $actual = $sut->toArray();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function jsonSerializeTest(): void
    {
        $result = Result::invalid(
            null,
            new MessageSet(new FieldName('aa', 'a'), new Message('%d', [2])),
            new MessageSet(new FieldName('a'), new Message('%d', [1])),
        );
        $expected = [
            'errors' => [],
            'fields' => [
                'a' => [
                    'errors' => ['1'],
                    'fields' => ['aa' => ['errors' => ['2'], 'fields' => []]],
                ],
            ],
        ];
        $sut = new JsonNested($result);

        $actual = $sut->jsonSerialize();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function toStringTest(): void
    {
        $result = Result::invalid(
            null,
            new MessageSet(new FieldName('a'), new Message('%d', [1])),
            new MessageSet(new FieldName('aa', 'a'), new Message('%d', [2])),
        );
        $expected = json_encode([
            'errors' => [],
            'fields' => [
                'a' => [
                    'errors' => ['1'],
                    'fields' => ['aa' => ['errors' => ['2'], 'fields' => []]],
                ],
            ],
        ]);
        $sut = new JsonNested($result);

        $actual = $sut->toString();

        self::assertSame($expected, $actual);
    }
}
