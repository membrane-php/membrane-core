<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Arrays extends APISchema
{
    public readonly V30\Schema|V31\Schema|null $items;
    public readonly ?int $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

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
            Type::Array,
            $schema->type
        );

        $this->items = $schema->items;
        $this->maxItems = $schema->maxItems;
        $this->minItems = $schema->minItems;
        $this->uniqueItems = $schema->uniqueItems;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
