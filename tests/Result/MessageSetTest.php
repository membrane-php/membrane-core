<?php

namespace Result;

use Membrane\Result\Fieldname;
use Membrane\Result\MessageSet;
use Membrane\Result\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Result\MessageSet
 * @uses \Membrane\Result\Fieldname
 * @uses \Membrane\Result\Message

 */
class MessageSetTest extends TestCase
{
    public function dataSetsThatCanMerge() : array
    {
        $fieldname = new Fieldname('field a');
        $firstMessage = new Message('message 1', ['a', 'c']);
        $secondMessage = new Message('message 2', ['b', 'd']);

        return [
            [
                new MessageSet(null, $firstMessage),
                new MessageSet(null, $secondMessage),
                new MessageSet(null, $firstMessage, $secondMessage)
            ],
            [
                new MessageSet(null, $firstMessage),
                new MessageSet($fieldname, $secondMessage),
                new MessageSet($fieldname, $firstMessage, $secondMessage)
            ],
            [
                new MessageSet($fieldname, $firstMessage),
                new MessageSet($fieldname, $secondMessage),
                new MessageSet($fieldname, $firstMessage, $secondMessage)
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatCanMerge
     */
    public function MergeMessageSets($firstInput, $secondInput, $expected) : void
    {
        $result = $firstInput->merge($secondInput);

        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function MergeDifferentFieldnameThrowsError() : void
    {
        $firstFieldname = new Fieldname('field a');
        $secondFieldname = new Fieldname('field b');
        $message = new Message('message', []);
        $firstSut = new MessageSet($firstFieldname, $message);
        $secondSut = new MessageSet($secondFieldname, $message);

        self::expectException('RuntimeException');
        self::expectExceptionMessage('Unable to merge message sets for different fieldnames');
        $result = $firstSut->merge($secondSut);
    }


}