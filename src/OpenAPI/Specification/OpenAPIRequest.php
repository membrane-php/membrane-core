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
use Membrane\OpenAPIReader\ValueObject\Valid\V30;

class OpenAPIRequest implements Specification
{
    /** @var V30\Parameter[] */
    public readonly array $parameters;
    public readonly string $operationId;
    public readonly ?V30\Schema $requestBodySchema;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        public readonly ExtractsPathParameters $pathParameterExtractor,
        private readonly V30\PathItem $path,
        public readonly Method $method,
    ) {
        $operation = $this->getOperation($this->path, $this->method);

        $this->parameters = $operation->parameters;

        assert(isset($operation->operationId));
        $this->operationId = $operation->operationId;

        $this->requestBodySchema = $operation->requestBody === null ?
            null
            :
            $this->getRequestBodySchema($operation->requestBody->content);
    }

    /** @param V30\MediaType[] $content */
    private function getRequestBodySchema(array $content): ?V30\Schema
    {
        if ($content === []) {
            return null;
        }

        foreach ($content as $contentType => $mediaType) {
            if (
                ContentType::fromContentTypeHeader($contentType) !== ContentType::Unmatched &&
                $mediaType->schema instanceof V30\Schema
            ) {
                return $mediaType->schema;
            }
        }

        throw CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($content));
    }

    private function getOperation(V30\PathItem $pathItem, Method $method): V30\Operation
    {
        return $pathItem->getOperations()[$method->value]
            ??
            throw CannotProcessSpecification::methodNotFound($method->value);
    }
}
