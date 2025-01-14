<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;

class Response implements Specification
{
    public readonly string $absoluteFilePath;

    public function __construct(
        string $absoluteFilePath,
        public readonly string $url,
        public readonly Method $method,
        public readonly string $statusCode
    ) {
        $this->absoluteFilePath = realpath($absoluteFilePath) ?: $absoluteFilePath;
    }
}
