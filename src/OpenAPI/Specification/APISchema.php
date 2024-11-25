<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\V30;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Type;

abstract class APISchema implements Specification
{
    /** @var mixed[] */
    public readonly ?array $enum;
    public readonly ?string $format;
    public readonly bool $nullable;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        public readonly string $fieldName,
        V30\Schema $schema
    ) {
        $this->enum = isset($schema->enum) ?
            array_map(fn($e) => $e->value, $schema->enum) :
            null;
        $this->format = $schema->format;
        $this->nullable = in_array(Type::Null, $schema->getTypes());
    }
}
