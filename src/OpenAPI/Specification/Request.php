<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Exception;
use Membrane\OpenAPI\Method;
use Psr\Http\Message\ServerRequestInterface;

class Request extends APISpec implements RequestSpec
{
    /** @var array<string,Parameter> */
    private readonly array $parameters;
    private readonly ?Schema $requestBodySchema;
    private readonly string $operationId;

    public function __construct(string $absoluteFilePath, string $url, Method $method)
    {
        parent::__construct($absoluteFilePath, $url, $method);

        $requestOperation = $this->getOperation($method);
        $this->operationId = $requestOperation->operationId ?? '';

        $requestBody = $requestOperation->requestBody ?? null;

        assert(!($requestBody instanceof Reference));
        $this->requestBodySchema = $requestBody === null ? null : $this->getSchema($requestBody->content);

        $this->parameters = $this->setParameters($this->getPathItem(), $requestOperation);
    }

    public static function fromPsr7(string $apiPath, ServerRequestInterface $request): self
    {
        $method = Method::tryFrom(strtolower($request->getMethod())) ?? throw new Exception('not supported');

        return new self($apiPath, $request->getUri()->getPath(), $method);
    }

    /** @return array<string,Parameter> */
    private function setParameters(PathItem $path, Operation $operation): array
    {
        $parameters = array_filter(
            array_merge($path->parameters, $operation->parameters),
            fn($p) => $p instanceof Parameter
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

    public function getRequestBody(): ?Schema
    {
        return $this->requestBodySchema;
    }
}
