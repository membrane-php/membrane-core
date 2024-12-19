<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use RuntimeException;

class Numeric extends APISchema
{
    public readonly bool $exclusiveMaximum;
    public readonly bool $exclusiveMinimum;
    public readonly float | int | null $maximum;
    public readonly float | int | null $minimum;
    public readonly float | int | null $multipleOf;
    public readonly string $type;

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

        $types = $schema->value->types;
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

        $this->exclusiveMaximum = $schema->value->maximum?->exclusive ?? false;
        $this->exclusiveMinimum = $schema->value->minimum?->exclusive ?? false;
        $this->maximum = $schema->value->maximum?->limit;
        $this->minimum = $schema->value->minimum?->limit;
        $this->multipleOf = $schema->value->multipleOf;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
