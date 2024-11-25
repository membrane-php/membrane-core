<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\TempHelpers\ChecksNumericTypeOrNull;
use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;

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
        V30\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        ChecksNumericTypeOrNull::check(
            self::class,
            $schema->type
        );

        $types = $schema->getTypes();

        if (in_array(Type::Integer, $types)) {
            $this->type = Type::Integer->value;
        } elseif (in_array(Type::Number, $types)) {
            $this->type = Type::Number->value;
        } else {
            throw CannotProcessSpecification::mismatchedType(
                self::class,
                'integer or number',
                implode(',', array_map(fn($t) => $t->value, $types)),
            );
        }

        $this->exclusiveMaximum = $schema->getRelevantMaximum()?->exclusive ?? false;
        $this->exclusiveMinimum = $schema->getRelevantMinimum()?->exclusive ?? false;
        $this->maximum = $schema->getRelevantMaximum()?->limit;
        $this->minimum = $schema->getRelevantMinimum()?->limit;
        $this->multipleOf = $schema->multipleOf;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
