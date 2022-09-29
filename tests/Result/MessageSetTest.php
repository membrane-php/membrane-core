<?php
declare(strict_types=1);

namespace Result;

use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\Message
 */
class MessageSetTest extends TestCase
{
    public function dataSetsThatCanMerge(): array
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

    /**
     * @test
     * @dataProvider dataSetsThatCanMerge
     */
    public function mergeMessageSets(MessageSet $firstInput, MessageSet $secondInput, MessageSet $expected): void
    {
        $result = $firstInput->merge($secondInput);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
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

    public function dataSetsForIsEmptyTest(): array
    {
        return [
            [new MessageSet(null), true],
            [new MessageSet(new FieldName('test field')), true],
            [new MessageSet(null, new Message('', [])), false],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForIsEmptyTest
     */
    public function isEmptyTest(MessageSet $messageSet, bool $expected): void
    {
        $result = $messageSet->isEmpty();

        self::assertEquals($expected, $result);
    }
}
