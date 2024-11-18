<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\TempHelpers\ChecksTypeSupported;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

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
        V30\Schema|V31\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        ChecksTypeSupported::check($schema->type);

        if (
            $schema->type === null
            || ! ($schema->canBe(Type::Number) || $schema->canBe(Type::Integer))
        ) {
            throw CannotProcessSpecification::mismatchedType(
                self::class,
                'integer or number',
                is_array($schema->type) ?
                    implode(',', array_map(fn($t) => $t->value, $schema->type)) :
                    $schema->type?->value,
            );
        }

        $this->type = $schema->canBe(Type::Integer) ? 'integer' : 'number';
        $this->exclusiveMaximum = $schema->getRelevantMaximum()?->exclusive ?? false;
        $this->exclusiveMinimum = $schema->getRelevantMinimum()?->exclusive ?? false;
        $this->maximum = $schema->getRelevantMaximum()?->limit;
        $this->minimum = $schema->getRelevantMinimum()?->limit;
        $this->multipleOf = $schema->multipleOf;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
