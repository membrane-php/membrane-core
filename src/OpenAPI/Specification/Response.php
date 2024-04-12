<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;

class Response implements Specification
{
    public function __construct(
        public readonly string $absoluteFilePath,
        public readonly string $url,
        public readonly Method $method,
        public readonly string $statusCode
    ) {
    }
}
