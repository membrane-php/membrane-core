<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

class OpenAPIResponse implements Specification
{
    public readonly V30\Schema | V31\Schema | null $schema;

    public function __construct(
        public readonly string $operationId,
        public readonly string $statusCode,
        V30\Response | V31\Response $response
    ) {
        $this->schema = $this->getSchema($response->content);
    }

    /** @param array<string, V30\MediaType | V31\MediaType> $content */
    private function getSchema(array $content): V30\Schema | V31\Schema | null
    {
        if ($content === []) {
            return null;
        }

        $schema = $content['application/json']?->schema
            ??
            throw CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($content));

        return $schema;
    }
}
