<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Router\ValueObject;

class Route
{
    /** @param string[] $servers */
    public function __construct(
        public readonly array $servers,
        public readonly string $path,
        public readonly string $method,
        public readonly string $operationId
    ) {
    }

}
