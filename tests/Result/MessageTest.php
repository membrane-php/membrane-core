<?php

declare(strict_types=1);

namespace Membrane\Tests\Result;

use Membrane\Result\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Message::class)]
class MessageTest extends TestCase
{
    #[Test]
    public function renderedMessageTest(): void
    {
        $expected = 'This message is a test';
        $message = new Message('This message is a %s', ['test']);

        $output = $message->rendered();

        self::assertEquals($expected, $output);
    }
}
