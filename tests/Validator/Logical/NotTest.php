<?php
declare(strict_types=1);

namespace Validator\Logical;

use Membrane\Result\Result;
use Membrane\Validator\Logical\Not;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Validator\Logical\Not
 */
class NotTest extends TestCase
{
    /**
     * @test
     */
    public function NotFailsAlwaysReturnsValid(): void
    {
        $notFail = new Not(new Fails);
        $result = $notFail->validate('any input will not fail');
        self::assertEquals(Result::VALID, $result->result);
    }

    /**
     * @test
     */
    public function NotPassesAlwaysReturnsInvalid(): void
    {
        $notPasses = new Not(new Passes);

        $result = $notPasses->validate('any input will not pass');
        self::assertEquals('Inner validator was valid', $result->messageSets[0]?->messages[0]?->message);
        self::assertEquals(Result::INVALID, $result->result);
    }

}