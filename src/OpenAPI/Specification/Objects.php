<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use RuntimeException;

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
        V30\Schema | V31\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
        public readonly ?bool $explode = null,
    ) {
        if (is_bool($schema->value)) {
            throw new RuntimeException('Any boolean schema should be dealt with before this point');
        }

        if (!in_array(Type::Object, $schema->value->types)) {
            throw CannotProcessSpecification::mismatchedType(
                ['object'],
                array_map(fn($t) => $t->value, $schema->value->types),
            );
        }

        $this->additionalProperties = $schema->value->additionalProperties;
        $this->properties = $schema->value->properties;
        $this->required = $schema->value->required;
        $this->maxProperties = $schema->value->maxProperties;
        $this->minProperties = $schema->value->minProperties;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
