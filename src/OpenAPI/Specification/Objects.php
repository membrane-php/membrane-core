<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Objects extends APISchema
{
    public function __construct(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
        public readonly ?bool $explode = null,
    ) {
        if (!in_array(Type::Object, $keywords->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['object'],
                array_map(fn($t) => $t->value, $keywords->types),
            );
        }

        parent::__construct($fieldName, $keywords);
    }
}
