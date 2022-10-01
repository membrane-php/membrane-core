<?php

namespace Membrane\Attribute;

use Membrane\Builder\Specification;

final class ClassWithAttributes implements Specification
{
    public function __construct(
        public readonly string $className
    ) {
    }
}
