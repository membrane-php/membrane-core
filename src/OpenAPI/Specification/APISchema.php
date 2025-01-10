<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

abstract class APISchema implements Specification
{
    /** @var mixed[] | null */
    public readonly ?array $enum;
    public readonly string $format;

    public function __construct(
        public readonly string $fieldName,
        V30\Keywords | V31\Keywords $keywords
    ) {
        $this->enum = isset($keywords->enum) ?
            array_map(fn($e) => $e->value, $keywords->enum) :
            null;
        $this->format = $keywords->format;
    }
}
