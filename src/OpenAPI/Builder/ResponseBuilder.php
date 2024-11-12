<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\Builder;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessResponse;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\OpenAPI\Specification\OpenAPIResponse;
use Membrane\OpenAPI\Specification\Response;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
use Membrane\Processor;

class ResponseBuilder implements Builder
{
    private OpenAPIResponseBuilder $responseBuilder;

    public function supports(Specification $specification): bool
    {
        return ($specification instanceof Response);
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof Response);

        $openAPI = (new Reader([OpenAPIVersion::Version_3_0, OpenAPIVersion::Version_3_1]))
            ->readFromAbsoluteFilePath($specification->absoluteFilePath);

        $serverUrl = $this->matchServer($openAPI, $specification->url);
        foreach ($openAPI->paths->getPaths() as $path => $pathItem) {
            $pathMatcher = new PathMatcher($serverUrl, $path);
            if (!$pathMatcher->matches($specification->url)) {
                continue;
            }

            $operation = $this->getOperation($pathItem, $specification->method);

            $response = $this->getResponse($operation, $specification->statusCode);

            $newSpecification = new OpenAPIResponse(
                OpenAPIVersion::fromString($openAPI->openapi),
                $operation->operationId,
                $specification->statusCode,
                $response
            );

            return $this->getOpenAPIResponseBuilder()->build($newSpecification);
        }

        throw CannotProcessSpecification::pathNotFound(
            pathinfo($specification->absoluteFilePath, PATHINFO_BASENAME),
            $specification->url
        );
    }

    private function getOpenAPIResponseBuilder(): OpenAPIResponseBuilder
    {
        if (!isset($this->responseBuilder)) {
            $this->responseBuilder = new OpenAPIResponseBuilder();
        }

        return $this->responseBuilder;
    }


    private function getOperation(Cebe\PathItem $pathItem, Method $method): Cebe\Operation
    {
        return $pathItem->getOperations()[$method->value]
            ??
            throw CannotProcessSpecification::methodNotFound($method->value);
    }

    private function getResponse(Cebe\Operation $operation, string $httpStatus): Cebe\Response
    {
        $response = $operation->responses?->getResponse($httpStatus) ??
            $operation->responses?->getResponse('default');

        assert(!$response instanceof Cebe\Reference);

        return $response ?? throw CannotProcessResponse::codeNotFound($httpStatus);
    }

    private function matchServer(Cebe\OpenApi $openAPI, string $url): string
    {
        $servers = $openAPI->servers;
        uasort($servers, fn($a, $b) => strlen($b->url) <=> strlen($a->url));

        foreach ($servers as $server) {
            $serverUrl = rtrim($server->url, '/');
            if (str_starts_with($url, $serverUrl . '/')) {
                return $serverUrl;
            }
        }

        return '';
    }
}
