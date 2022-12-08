<?php

declare(strict_types=1);

namespace Membrane\Attribute;

use Exception;
use Membrane\Builder\Specification;

final class ClassWithAttributes implements Specification
{
    public function __construct(
        public readonly string $className
    ) {
        if (!class_exists($this->className)) {
            throw new Exception(sprintf('Could not find class %s', $this->className));
        }
    }
}
