<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\PathMatcher;
use Membrane\OpenAPI\Reader\OpenAPIFileReader;

use function str_starts_with;

abstract class APISpec implements Specification
{
    public readonly PathItem $pathItem;
    public readonly PathMatcher $matchingPath;

    // @TODO support alternative servers found in both Path or PathItem objects

    public function __construct(string $absoluteFilePath, string $url)
    {
        $openAPI = (new OpenAPIFileReader())->readFromAbsoluteFilePath($absoluteFilePath);

        $serverUrl = $this->matchServer($openAPI, $url);
        foreach ($openAPI->paths->getPaths() as $path => $pathItem) {
            $pathMatcher = new PathMatcher($serverUrl, $path);
            if ($pathMatcher->matches($url)) {
                $this->matchingPath = $pathMatcher;
                $this->pathItem = $pathItem;
                break;
            }
        }

            $this->matchingPath ?? throw CannotProcessRequest::pathNotFound(
                pathinfo($absoluteFilePath, PATHINFO_BASENAME),
                $url
            );
    }

    protected function getOperation(Method $method): Operation
    {
        return $this->pathItem->getOperations()[$method->value]
            ??
            throw CannotProcessRequest::methodNotFound($method->value);
    }

    /** @param MediaType[] $content */
    protected function getSchema(array $content): ?Schema
    {
        if ($content === []) {
            return null;
        }

        $schema = $content['application/json']?->schema ?? throw CannotProcessRequest::unsupportedContent();

        assert($schema instanceof Schema);
        return $schema;
    }

    private function matchServer(OpenApi $openAPI, string $url): string
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
