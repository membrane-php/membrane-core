<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type, V30, V31};
use RuntimeException;

class Strings extends APISchema
{
    public readonly ?int $maxLength;
    public readonly int $minLength;
    public readonly ?string $pattern;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        V30\Schema | V31\Schema $schema,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        if (is_bool($schema->value)) {
            throw new RuntimeException('Any boolean schema should be dealt with before this point');
        }

        if (!in_array(Type::String, $schema->value->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['string'],
                array_map(fn($t) => $t->value, $schema->value->types),
            );
        }

        $this->maxLength = $schema->value->maxLength;
        $this->minLength = $schema->value->minLength;
        $this->pattern = $schema->value->pattern;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
