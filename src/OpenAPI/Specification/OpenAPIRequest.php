<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\ExtractsPathParameters;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;

class OpenAPIRequest implements Specification
{
    /** @var array<string, Cebe\Parameter> */
    public readonly array $parameters;
    public readonly string $operationId;
    public readonly ?Cebe\Schema $requestBodySchema;

    public function __construct(
        public readonly OpenAPIVersion $openAPIVersion,
        public readonly ExtractsPathParameters $pathParameterExtractor,
        private readonly Cebe\PathItem $path,
        public readonly Method $method,
    ) {
        $operation = $this->getOperation($this->path, $this->method);

        $this->parameters = $this->getParameters($this->path, $operation);

        assert(isset($operation->operationId));
        $this->operationId = $operation->operationId;

        assert(!$operation->requestBody instanceof Cebe\Reference);
        $this->requestBodySchema = $operation->requestBody === null ?
            null
            :
            $this->getRequestBodySchema($operation->requestBody->content);
    }

    /** @param Cebe\MediaType[] $content */
    private function getRequestBodySchema(array $content): ?Cebe\Schema
    {
        if ($content === []) {
            return null;
        }

        foreach ($content as $contentType => $mediaType) {
            if (
                ContentType::fromContentTypeHeader($contentType) !== ContentType::Unmatched &&
                $mediaType->schema instanceof Cebe\Schema
            ) {
                return $mediaType->schema;
            }
        }

        throw CannotProcessOpenAPI::unsupportedMediaTypes(...array_keys($content));
    }

    private function getOperation(Cebe\PathItem $pathItem, Method $method): Cebe\Operation
    {
        return $pathItem->getOperations()[$method->value]
            ??
            throw CannotProcessSpecification::methodNotFound($method->value);
    }

    /** @return array<string,Cebe\Parameter> */
    private function getParameters(Cebe\PathItem $path, Cebe\Operation $operation): array
    {
        $parameters = array_filter(
            array_merge($path->parameters, $operation->parameters),
            fn($p) => $p instanceof Cebe\Parameter
        );

        $parameters = array_combine(array_map(fn($p) => $p->name, $parameters), $parameters);

        return $parameters;
    }
}
