<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Objects extends APISchema
{
    // @TODO support minProperties and maxProperties
    public readonly V30\Schema | V31\Schema $additionalProperties;
    /** @var V30\Schema[] | V31\Schema[] */
    public readonly array $properties;
    /** @var string[] */
    public readonly array $required;

    public readonly ?int $maxProperties;
    public readonly int $minProperties;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        V30\Keywords | V31\Keywords $keywords,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
        public readonly ?bool $explode = null,
    ) {
        if (!in_array(Type::Object, $keywords->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['object'],
                array_map(fn($t) => $t->value, $keywords->types),
            );
        }

        $this->additionalProperties = $keywords->additionalProperties;
        $this->properties = $keywords->properties;
        $this->required = $keywords->required;
        $this->maxProperties = $keywords->maxProperties;
        $this->minProperties = $keywords->minProperties;

        parent::__construct($openAPIVersion, $fieldName, $keywords);
    }
}
