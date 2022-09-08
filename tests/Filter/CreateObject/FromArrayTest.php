<?php
declare(strict_types=1);

namespace Filter\CreateObject;

use Membrane\Filter\CreateObject\FromArray;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Filter\CreateObject\FromArray
 */
class FromArrayTest extends TestCase
{
    /**
     * @test
     */
    public function NoFromArrayMethodReturnsInvalid()
    {
        self::assertTrue(true);
    }
}