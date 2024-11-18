<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Objects extends APISchema
{
    // @TODO support minProperties and maxProperties
    public readonly bool |V30\Schema|V31\Schema $additionalProperties;
    /** @var V30\Schema[]|V31\Schema[] */
    public readonly array $properties;
    /** @var string[]|null */
    public readonly ?array $required;

    public readonly ?int $maxProperties;
    public readonly int $minProperties;

    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        V30\Schema|V31\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
        public readonly ?bool $explode = null,
    ) {
        ChecksOnlyTypeOrNull::check(
            self::class,
            Type::Object,
            $schema->type,
        );

        $this->additionalProperties = $schema->additionalProperties;

        $this->properties = $schema->properties;

        $this->required = $schema->required;

        $this->maxProperties = $schema->maxProperties;

        $this->minProperties = $schema->minProperties;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
