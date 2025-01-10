<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\Factory;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Type, V30, V31};

class Strings extends APISchema
{
    public readonly ?int $maxLength;
    public readonly int $minLength;
    public readonly ?string $pattern;

    public function __construct(
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        if (!in_array(Type::String, $keywords->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['string'],
                array_map(fn($t) => $t->value, $keywords->types),
            );
        }

        $this->maxLength = $keywords->maxLength;
        $this->minLength = $keywords->minLength;
        $this->pattern = $keywords->pattern;

        parent::__construct($fieldName, $keywords);
    }
}
