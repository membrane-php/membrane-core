<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use RuntimeException;

class Arrays extends APISchema
{
    public readonly V30\Schema | V31\Schema $items;
    public readonly ?int $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        V30\Schema | V31\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
        public readonly ?bool $explode = null,
    ) {
        if (is_bool($schema->value)) {
            throw new RuntimeException('Any boolean schema should be dealt with before this point');
        }

        if (!in_array(Type::Array, $schema->value->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['array'],
                array_map(fn($t) => $t->value, $schema->value->types),
            );
        }

        $this->items = $schema->value->items;
        $this->maxItems = $schema->value->maxItems;
        $this->minItems = $schema->value->minItems;
        $this->uniqueItems = $schema->value->uniqueItems;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
