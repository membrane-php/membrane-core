<?php

namespace Membrane\Attribute;

use Membrane\Filter;
use Membrane\Validator;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_PROPERTY)]
class FilterOrValidator
{
    public function __construct(
        public readonly Validator|Filter $class
    ) {
    }
}
