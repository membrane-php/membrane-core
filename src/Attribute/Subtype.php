<?php

namespace Membrane\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Subtype
{
    public function __construct(
        public readonly string $type
    ) {
    }
}