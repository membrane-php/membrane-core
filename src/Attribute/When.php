<?php

declare(strict_types=1);

namespace Membrane\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE + \Attribute::TARGET_PROPERTY)]
final class When
{
    public function __construct(
        public string $typeIs,
        public FilterOrValidator $filterOrValidator,
    ) {
    }
}
