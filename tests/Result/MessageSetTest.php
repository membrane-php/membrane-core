<?php

declare(strict_types=1);

namespace Result;

use Membrane\Result\Fieldname;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Fieldname
 * @uses   \Membrane\Result\Message
 */
class MessageSetTest extends TestCase
{
    public function dataSetsThatCanMerge(): array
    {
        $fieldname = new Fieldname('field a');
        $firstMessage = new Message('message 1', ['a', 'c']);
        $secondMessage = new Message('message 2', ['b', 'd']);

        return [
            'MessageSets with equal fieldnames' => [
                new MessageSet($fieldname, $firstMessage),
                new MessageSet($fieldname, $secondMessage),
                new MessageSet($fieldname, $firstMessage, $secondMessage),
            ],
            'MessageSets with all null fieldnames' => [
                new MessageSet(null, $firstMessage),
                new MessageSet(null, $secondMessage),
                new MessageSet(null, $firstMessage, $secondMessage),
            ],
            'MessageSets with one null fieldname' => [
                new MessageSet(null, $firstMessage),
                new MessageSet($fieldname, $secondMessage),
                new MessageSet($fieldname, $firstMessage, $secondMessage),
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
    public function mergeDifferentFieldnameThrowsError(): void
    {
        $firstFieldname = new Fieldname('field a');
        $secondFieldname = new Fieldname('field b');
        $message = new Message('message', []);
        $firstMessageSet = new MessageSet($firstFieldname, $message);
        $secondMessageSet = new MessageSet($secondFieldname, $message);

        self::expectException('RuntimeException');
        self::expectExceptionMessage('Unable to merge message sets for different fieldnames');
        $result = $firstMessageSet->merge($secondMessageSet);
    }

    public function dataSetsForIsEmptyTest(): array
    {
        return [
            [new MessageSet(null), true],
            [new MessageSet(new Fieldname('test field')), true],
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
