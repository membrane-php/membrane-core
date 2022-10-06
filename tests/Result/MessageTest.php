<?php

declare(strict_types=1);

namespace Result;

use Membrane\Result\Message;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Result\Message
 */
class MessageTest extends TestCase
{
    /**
     * @test
     */
    public function renderedMessageTest(): void
    {
        $expected = 'This message is a test';
        $message = new Message('This message is a %s', ['test']);

        $output = $message->rendered();

        self::assertEquals($expected, $output);
    }
}
