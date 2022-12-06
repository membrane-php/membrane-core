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

class Request extends APISpec
{
    /** @var Parameter[] */
    public readonly array $pathParameters;
    public readonly ?Schema $requestBodySchema;

    public function __construct(string $filePath, string $url, Method $method)
    {
        parent::__construct($filePath, $url);

        $requestOperation = $this->getOperation($method);

        $requestBody = $requestOperation->requestBody ?? null;

        assert(!($requestBody instanceof Reference));
        $this->requestBodySchema = $requestBody === null ? null : $this->getSchema($requestBody->content);

        $this->pathParameters = $this->getPathParameters($this->pathItem, $requestOperation);
    }

    public static function fromPsr7(string $apiPath, ServerRequestInterface $request): self
    {
        $method = Method::tryFrom(strtolower($request->getMethod())) ?? throw new Exception('not supported');

        return new self($apiPath, $request->getUri()->getPath(), $method);
    }

    /** @return Parameter[] */
    private function getPathParameters(PathItem $path, Operation $operation): array
    {
        $parameters = array_filter(
            array_merge($path->parameters, $operation->parameters),
            fn($p) => $p instanceof Parameter
        );

        $parameters = array_combine(array_map(fn($p) => $p->name, $parameters), $parameters);

        return array_values($parameters);
    }
}
