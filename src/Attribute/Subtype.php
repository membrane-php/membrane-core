<?php

declare(strict_types=1);

namespace Membrane\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Subtype
{
    public function __construct(
        public readonly string $type
    ) {
    }
}
