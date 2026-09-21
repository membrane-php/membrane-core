<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type, V30, V31};

class Strings extends APISchema
{
    public function __construct(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        parent::__construct($fieldName, $keywords);
    }
}
