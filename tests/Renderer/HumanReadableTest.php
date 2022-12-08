<?php

declare(strict_types=1);

namespace Renderer;

use Membrane\Renderer\HumanReadable;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Renderer\HumanReadable
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Result
 */
class HumanReadableTest extends TestCase
{
    public function dataSetsToRenderAsArrays(): array
    {
        $msg = fn($var) => new Message('%s', [$var]);

        return [
            'no messages' => [
                Result::valid(null),
                [],
            ],
            'empty messageSet' => [
                Result::invalid(null, new MessageSet(null)),
                [],
            ],
            'no fieldName' => [
                Result::invalid(null, new MessageSet(null, $msg(1))),
                ['' => ['1']],
            ],
            'empty string fieldName' => [
                Result::invalid(null, new MessageSet(new FieldName(''), $msg(1))),
                ['' => ['1']],
            ],
            'single one-level message' => [
                Result::invalid(null, new MessageSet(new FieldName('a'), $msg(1))),
                ['a' => ['1']],
            ],
            'single two-level message' => [
                Result::invalid(null, new MessageSet(new FieldName('aa', 'a'), $msg(1))),
                ['a->aa' => ['1']],
            ],
            'multiple messages' => [
                Result::invalid(null, new MessageSet(new FieldName('aa', 'a'), $msg(1), $msg(2))),
                ['a->aa' => ['1', '2']],
            ],
            'multiple unrelated messageSets' => [
                Result::invalid(
                    null,
                    new MessageSet(new FieldName('aa', 'a'), $msg(1), $msg(2)),
                    new MessageSet(new FieldName('bbb', 'b', 'bb'), $msg(3)),
                ),
                ['a->aa' => ['1', '2'], 'b->bb->bbb' => ['3']],
            ],
            'multiple related messageSets' => [
                Result::invalid(
                    null,
                    new MessageSet(new FieldName('a'), $msg(1), $msg(2)),
                    new MessageSet(new FieldName('a'), $msg(3)),
                ),
                ['a' => ['1', '2', '3']],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToRenderAsArrays
     */
    public function toArrayTest(Result $result, array $expected): void
    {
        $sut = new HumanReadable($result);

        $actual = $sut->toArray();

        self::assertSame($expected, $actual);
    }

    /** @test */
    public function jsonSerializeTest(): void
    {
        $result = Result::invalid(
            null,
            new MessageSet(new FieldName('a'), new Message('%d', [1]), new Message('%d', [2])),
            new MessageSet(new FieldName('a'), new Message('%d', [3])),
        );
        $expected = ['a' => ['1', '2', '3']];
        $sut = new HumanReadable($result);

        $actual = $sut->jsonSerialize();

        self::assertSame($expected, $actual);
    }


    public function dataSetsToRenderAsStrings(): array
    {
        $msg = fn($var) => new Message('%s', [$var]);

        return [
            'no messages' => [
                Result::valid(null),
                '',
            ],
            'empty messageSet' => [
                Result::invalid(null, new MessageSet(null)),
                '',
            ],
            'no fieldName' => [
                Result::invalid(null, new MessageSet(null, $msg(1))),
                '- 1',
            ],
            'empty string fieldName' => [
                Result::invalid(null, new MessageSet(new FieldName(''), $msg(1))),
                '- 1',
            ],
            'single one-level message' => [
                Result::invalid(null, new MessageSet(new FieldName('a'), $msg(1))),
                "a\n\t- 1",
            ],
            'single two-level message' => [
                Result::invalid(null, new MessageSet(new FieldName('aa', 'a'), $msg(1))),
                "a->aa\n\t- 1",
            ],
            'multiple messages' => [
                Result::invalid(null, new MessageSet(new FieldName('aa', 'a'), $msg(1), $msg(2))),
                "a->aa\n\t- 1\n\t- 2",
            ],
            'multiple messageSets' => [
                Result::invalid(
                    null,
                    new MessageSet(new FieldName('aa', 'a'), $msg(1), $msg(2)),
                    new MessageSet(new FieldName('bbb', 'b', 'bb'), $msg(3)),
                ),
                "a->aa\n\t- 1\n\t- 2\nb->bb->bbb\n\t- 3",
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToRenderAsStrings
     */
    public function renderTest(Result $result, string $expected): void
    {
        $sut = new HumanReadable($result);

        $actual = $sut->toString();

        self::assertSame($expected, $actual);
    }
}
