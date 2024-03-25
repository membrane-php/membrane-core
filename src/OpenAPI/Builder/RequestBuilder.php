<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec as Cebe;
use Membrane\Builder\{Builder, Specification};
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\PathMatcher;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPI\Specification\Request;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
use Membrane\Processor;

class RequestBuilder implements Builder
{
    private OpenAPIRequestBuilder $requestBuilder;

    public function supports(Specification $specification): bool
    {
        return $specification instanceof Request;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof Request);

        $openAPI = (new Reader([OpenAPIVersion::Version_3_0]))
            ->readFromAbsoluteFilePath($specification->absoluteFilePath);

        $serverUrl = $this->matchServer($openAPI, $specification->url);
        foreach ($openAPI->paths->getPaths() as $path => $pathItem) {
            $pathMatcher = new PathMatcher($serverUrl, $path);
            if (!$pathMatcher->matches($specification->url)) {
                continue;
            }

            $newSpecification = new OpenAPIRequest($pathMatcher, $pathItem, $specification->method);

            return $this->getOpenAPIRequestBuilder()->build($newSpecification);
        }

        throw CannotProcessSpecification::pathNotFound(
            pathinfo($specification->absoluteFilePath, PATHINFO_BASENAME),
            $specification->url
        );
    }

    private function getOpenAPIRequestBuilder(): OpenAPIRequestBuilder
    {
        if (!isset($this->requestBuilder)) {
            $this->requestBuilder = new OpenAPIRequestBuilder();
        }

        return $this->requestBuilder;
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
