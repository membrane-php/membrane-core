<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPIReader\OpenAPIVersion;
use RuntimeException;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class Arrays extends APISchema
{
    public readonly V30\Schema | V31\Schema $items;
    public readonly ?int $maxItems;
    public readonly int $minItems;
    public readonly bool $uniqueItems;

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

        ChecksOnlyTypeOrNull::check(
            self::class,
            Type::Array,
            $schema->value->types
        );

        $this->items = $schema->value->items;
        $this->maxItems = $schema->value->maxItems;
        $this->minItems = $schema->value->minItems;
        $this->uniqueItems = $schema->value->uniqueItems;

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
