<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use RuntimeException;

class TrueFalse extends APISchema
{
    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        V30\Schema | V31\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        if (is_bool($schema->value)) {
            throw new RuntimeException('Any boolean schema should be dealt with before this point');
        }

        if (!in_array(Type::Boolean, $schema->value->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['boolean'],
                array_map(fn($t) => $t->value, $schema->value->types),
            );
        }

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
