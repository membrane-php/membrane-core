<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Schema;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\TempHelpers\ChecksTypeSupported;
use Membrane\OpenAPI\TempHelpers\CreatesSchema;
use Membrane\OpenAPIReader\OpenAPIVersion;
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
        Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        $membraneSchema = CreatesSchema::create($openAPIVersion, $fieldName, $schema);

        ChecksTypeSupported::check($membraneSchema->type);

        if (
            $membraneSchema->type === null
            || ! ($membraneSchema->canBe(Type::Number) || $membraneSchema->canBe(Type::Integer))
        ) {
            throw CannotProcessSpecification::mismatchedType(
                self::class,
                'integer or number',
                is_array($membraneSchema->type) ?
                    implode(',', array_map(fn($t) => $t->value, $membraneSchema->type)) :
                    $membraneSchema->type?->value,
            );
        }

        $this->type = $membraneSchema->canBe(Type::Integer) ? 'integer' : 'number';
        $this->exclusiveMaximum = $membraneSchema->getRelevantMaximum()?->exclusive ?? false;
        $this->exclusiveMinimum = $membraneSchema->getRelevantMinimum()?->exclusive ?? false;
        $this->maximum = $membraneSchema->getRelevantMaximum()?->limit;
        $this->minimum = $membraneSchema->getRelevantMinimum()?->limit;
        $this->multipleOf = $membraneSchema->multipleOf;

        parent::__construct($openAPIVersion, $fieldName, $membraneSchema);
    }
}
