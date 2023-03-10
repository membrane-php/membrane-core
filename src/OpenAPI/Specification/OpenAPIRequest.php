<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec as Cebe;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\ExtractPathParameters\ExtractsPathParameters;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Method;

class OpenAPIRequest implements RequestSpec
{
    /** @var array<string, Cebe\Parameter> */
    private readonly array $parameters;
    private readonly ExtractsPathParameters $pathParameterExtractor;
    private readonly string $operationId;
    private readonly ?Cebe\Schema $requestBodySchema;

    public function __construct(
        string $pathUrl,
        private readonly Cebe\PathItem $path,
        private readonly Method $method,
    ) {
        $operation = $this->path->getOperations()[$this->method->value] ?? throw new \Exception('operation not found');

        $this->parameters = $this->setParameters($this->path, $operation);
        $this->operationId = $operation->operationId ?? throw new \Exception('requires operationId');

        assert(!$operation->requestBody instanceof Cebe\Reference);
        $this->requestBodySchema = $operation->requestBody === null ? null : $this->setRequestBodySchema(
            $operation->requestBody->content
        );

        $this->pathParameterExtractor = new PathParameterExtractor($pathUrl);
    }

    /** @param Cebe\MediaType[] $content */
    private function setRequestBodySchema(array $content): ?Cebe\Schema
    {
        if ($content === []) {
            return null;
        }

        $schema = $content['application/json']?->schema ?? throw CannotProcessRequest::unsupportedContent();

        assert($schema instanceof Cebe\Schema);
        return $schema;
    }

    /** @return array<string,Cebe\Parameter> */
    private function setParameters(Cebe\PathItem $path, Cebe\Operation $operation): array
    {
        $parameters = array_filter(
            array_merge($path->parameters, $operation->parameters),
            fn($p) => $p instanceof Cebe\Parameter
        );

        $parameters = array_combine(array_map(fn($p) => $p->name, $parameters), $parameters);

        return $parameters;
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getRequestBody(): ?Cebe\Schema
    {
        return $this->requestBodySchema;
    }

    public function getMethod(): Method
    {
        return $this->method;
    }

    public function getPathItem(): Cebe\PathItem
    {
        return $this->path;
    }

    public function getPathParameterExtractor(): ExtractsPathParameters
    {
        return $this->pathParameterExtractor;
    }
}
