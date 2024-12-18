<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use Membrane\Builder\Specification;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\ExtractsPathParameters;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};

class OpenAPIRequest implements Specification
{
    /** @var V30\Parameter[] | V31\Parameter[] */
    public readonly array $parameters;
    public readonly string $operationId;
    public readonly V30\Schema | V31\Schema | null $requestBodySchema;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        public readonly ExtractsPathParameters $pathParameterExtractor,
        private readonly V30\PathItem | V31\PathItem $path,
        public readonly Method $method,
    ) {
        $operation = $this->getOperation($this->path, $this->method);

        $this->parameters = $operation->parameters;

        assert(isset($operation->operationId));
        $this->operationId = $operation->operationId;

        $this->requestBodySchema = isset($operation->requestBody) ?
            $this->getRequestBodySchema($operation->requestBody->content) :
            null;
    }

    /** @param array<string, V30\MediaType | V31\MediaType> $content */
    private function getRequestBodySchema(array $content): V30\Schema | V31\Schema | null
    {
        if ($content === []) {
            return null;
        }

        foreach ($content as $contentType => $mediaType) {
            if (
                ContentType::fromContentTypeHeader($contentType) !== ContentType::Unmatched
                && isset($mediaType->schema)
            ) {
                return $mediaType->schema;
            }
        }

        throw CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($content));
    }

    private function getOperation(
        V30\PathItem | V31\PathItem $pathItem,
        Method $method
    ): V30\Operation | V31\Operation {
        return $pathItem->getOperations()[$method->value]
            ?? throw CannotProcessSpecification::methodNotFound($method->value);
    }
}
