<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;
use RuntimeException;

class TrueFalse extends APISchema
{
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

        ChecksOnlyTypeOrNull::check(
            self::class,
            Type::Boolean,
            $schema->value->types
        );

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
