<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Numeric extends APISchema
{
    public readonly string $type;

    public function __construct(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        $types = $keywords->types;
        if (in_array(Type::Integer, $types)) {
            $this->type = Type::Integer->value;
        } elseif (in_array(Type::Number, $types)) {
            $this->type = Type::Number->value;
        } else {
            throw CannotProcessSpecification::mismatchedType(
                ['integer', 'number'],
                array_map(fn($t) => $t->value, $types),
            );
        }

        parent::__construct($fieldName, $keywords);
    }
}
