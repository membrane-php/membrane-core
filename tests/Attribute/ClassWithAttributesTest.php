<?php

declare(strict_types=1);

namespace Attribute;

use Membrane\Attribute\ClassWithAttributes;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Attribute\ClassWithAttributes
 */
class ClassWithAttributesTest extends TestCase
{
    /**
     * @test
     */
    public function passingNonExistentClassNameToFromClassThrowsException(): void
    {
        self::expectException('Exception');
        self::expectExceptionMessage('Could not find class NotAClass');

        new ClassWithAttributes('NotAClass');
    }
}
