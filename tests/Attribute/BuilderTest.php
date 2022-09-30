<?php

declare(strict_types=1);

namespace Attribute;

use Membrane\Attribute\Builder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\Attribute\Builder
 */
class BuilderTest extends TestCase
{
    /**
     * @test
     */
    public function passingNonExistentClassNameToFromClassThrowsException(): void
    {
        $builder = new Builder();
        self::expectException('Exception');
        self::expectExceptionMessage('Could not find class NotAClass');

        $builder->fromClass('NotAClass');
    }
}
