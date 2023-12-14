<?php

declare(strict_types=1);

namespace Membrane\Tests\Result;

use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MessageSet::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(Message::class)]
class MessageSetTest extends TestCase
{
    public static function dataSetsThatCanMerge(): array
    {
        $fieldName = new FieldName('field a');
        $firstMessage = new Message('message 1', ['a', 'c']);
        $secondMessage = new Message('message 2', ['b', 'd']);

        return [
            'MessageSets with equal fieldnames' => [
                new MessageSet($fieldName, $firstMessage),
                new MessageSet($fieldName, $secondMessage),
                new MessageSet($fieldName, $firstMessage, $secondMessage),
            ],
            'MessageSets with all null fieldnames' => [
                new MessageSet(null, $firstMessage),
                new MessageSet(null, $secondMessage),
                new MessageSet(null, $firstMessage, $secondMessage),
            ],
            'MessageSets with one null fieldName' => [
                new MessageSet(null, $firstMessage),
                new MessageSet($fieldName, $secondMessage),
                new MessageSet($fieldName, $firstMessage, $secondMessage),
            ],

        ];
    }

    #[DataProvider('dataSetsThatCanMerge')]
    #[Test]
    public function mergeMessageSets(MessageSet $firstInput, MessageSet $secondInput, MessageSet $expected): void
    {
        $result = $firstInput->merge($secondInput);

        self::assertEquals($expected, $result);
    }

    #[Test]
    public function mergeDifferentFieldNameThrowsError(): void
    {
        $firstFieldName = new FieldName('field a');
        $secondFieldName = new FieldName('field b');
        $message = new Message('message', []);
        $firstMessageSet = new MessageSet($firstFieldName, $message);
        $secondMessageSet = new MessageSet($secondFieldName, $message);

        self::expectException('RuntimeException');
        self::expectExceptionMessage('Unable to merge message sets for different fieldNames');

        $firstMessageSet->merge($secondMessageSet);
    }

    public static function dataSetsForIsEmptyTest(): array
    {
        return [
            [new MessageSet(null), true],
            [new MessageSet(new FieldName('test field')), true],
            [new MessageSet(null, new Message('', [])), false],
        ];
    }

    #[DataProvider('dataSetsForIsEmptyTest')]
    #[Test]
    public function isEmptyTest(MessageSet $messageSet, bool $expected): void
    {
        $result = $messageSet->isEmpty();

        self::assertEquals($expected, $result);
    }
}
