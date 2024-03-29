<?php

declare(strict_types=1);

namespace Membrane\Tests\Renderer;

use Membrane\Renderer\JsonFlat;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonFlat::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Result::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
class JsonFlatTest extends TestCase
{
    public static function dataSetsToRenderAsArrays(): array
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

    #[DataProvider('dataSetsToRenderAsArrays')]
    #[Test]
    public function toArrayTest(Result $result, array $expected): void
    {
        $sut = new JsonFlat($result);

        $actual = $sut->toArray();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function JsonSerializeTest(): void
    {
        $result = Result::invalid(
            null,
            new MessageSet(new FieldName('a'), new Message('%d', [1]), new Message('%d', [2])),
            new MessageSet(new FieldName('a'), new Message('%d', [3])),
        );
        $expected = ['a' => ['1', '2', '3']];
        $sut = new JsonFlat($result);

        $actual = $sut->jsonSerialize();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function toStringTest(): void
    {
        $result = Result::invalid(
            null,
            new MessageSet(new FieldName('a'), new Message('%d', [1]), new Message('%d', [2])),
            new MessageSet(new FieldName('a'), new Message('%d', [3])),
        );
        $expected = '{"a":["1","2","3"]}';
        $sut = new JsonFlat($result);

        $actual = $sut->toString();

        self::assertSame($expected, $actual);
    }
}
