<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\OpenAPI\TempHelpers\ChecksOnlyTypeOrNull;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

class TrueFalse extends APISchema
{
    public function __construct(
        OpenAPIVersion $openAPIVersion,
        string $fieldName,
        V30\Schema|V31\Schema $schema,
        public readonly bool $convertFromString = false,
        public readonly bool $convertFromArray = false,
        public readonly ?string $style = null,
    ) {
        ChecksOnlyTypeOrNull::check(
            self::class,
            Type::Boolean,
            $schema->type
        );

        parent::__construct($openAPIVersion, $fieldName, $schema);
    }
}
