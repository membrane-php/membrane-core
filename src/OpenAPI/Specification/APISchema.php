<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

abstract class APISchema implements Specification
{
    public function __construct(
        public readonly string $fieldName,
        public V30\Keywords | V31\Keywords $keywords
    ) {
    }
}
