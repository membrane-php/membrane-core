<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;

class OpenAPIResponse implements Specification
{
    public readonly ?Cebe\Schema $schema;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        public readonly string $operationId,
        public readonly string $statusCode,
        Cebe\Response $response
    ) {
        $this->schema = $this->getSchema($response->content);
    }

    /** @param Cebe\MediaType[] $content */
    private function getSchema(array $content): ?Cebe\Schema
    {
        if ($content === []) {
            return null;
        }

        $schema = $content['application/json']?->schema
            ??
            throw CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($content));

        assert($schema instanceof Cebe\Schema);
        return $schema;
    }
}
