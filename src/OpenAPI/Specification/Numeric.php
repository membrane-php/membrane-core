<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

class Numeric extends APISchema
{
    public function __construct(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        parent::__construct($fieldName, $keywords);
    }
}
