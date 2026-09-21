<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Arrays extends APISchema
{
    public readonly V30\Schema | V31\Schema $items;

    public function __construct(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
        public readonly ?bool $explode = null,
    ) {
        if (!in_array(Type::Array, $keywords->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['array'],
                array_map(fn($t) => $t->value, $keywords->types),
            );
        }

        $this->items = $keywords->items;

        parent::__construct($fieldName, $keywords);
    }
}
