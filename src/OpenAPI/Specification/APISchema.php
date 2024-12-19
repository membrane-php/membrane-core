<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use RuntimeException;

abstract class APISchema implements Specification
{
    /** @var mixed[] | null */
    public readonly ?array $enum;
    public readonly string $format;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        public readonly string $fieldName,
        V30\Schema | V31\Schema $schema
    ) {
        if (is_bool($schema->value)) {
            throw new RuntimeException('Any boolean schema should be dealt with before this point');
        }

        $this->enum = isset($schema->value->enum) ?
            array_map(fn($e) => $e->value, $schema->value->enum) :
            null;
        $this->format = $schema->value->format;
    }
}
