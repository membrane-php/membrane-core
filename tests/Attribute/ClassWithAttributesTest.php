<?php

declare(strict_types=1);

namespace Membrane\Tests\Attribute;

use Membrane\Attribute\ClassWithAttributes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(\Membrane\Attribute\ClassWithAttributes::class)]
class ClassWithAttributesTest extends TestCase
{
    #[Test]
    public function passingNonExistentClassNameToFromClassThrowsException(): void
    {
        self::expectException('Exception');
        self::expectExceptionMessage('Could not find class NotAClass');

        new ClassWithAttributes('NotAClass');
    }
}
