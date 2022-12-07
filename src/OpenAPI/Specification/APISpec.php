<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\Reader;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Exception;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\PathMatcher;
use Throwable;

use function str_starts_with;

abstract class APISpec implements Specification
{
    public readonly PathItem $pathItem;
    public readonly PathMatcher $matchingPath;

    // @TODO support alternative servers found in both Path or PathItem objects

    public function __construct(string $filePath, string $url)
    {
        $openAPI = $this->loadOpenAPIFile($filePath);
        $openAPI->validate() ?: throw new Exception('OpenAPI could not be validated');

        $serverUrl = $this->matchServer($openAPI, $url);
        foreach ($openAPI->paths->getPaths() as $path => $pathItem) {
            $pathMatcher = new PathMatcher($serverUrl, $path);
            if ($pathMatcher->matches($url)) {
                $this->matchingPath = $pathMatcher;
                $this->pathItem = $pathItem;
                break;
            }
        }

            $this->matchingPath ?? throw new Exception('url does not match any paths defined by API');
    }

    protected function getOperation(Method $method): Operation
    {
        return $this->pathItem->getOperations()[$method->value]
            ??
            throw new Exception('Method does not match any methods defined by path');
    }

    /** @param MediaType[] $content */
    protected function getSchema(array $content): ?Schema
    {
        if ($content === []) {
            return null;
        }

        $schema = $content['application/json']?->schema
            ??
            throw new Exception('APISpec requires application/json content');

        assert($schema instanceof Schema);
        return $schema;
    }


    private function loadOpenAPIFile(string $filePath): OpenApi
    {
        if (!file_exists($filePath)) {
            throw new Exception('File could not be found');
        }

        $fileExtension = pathinfo(strtolower($filePath), PATHINFO_EXTENSION);
        try {
            if ($fileExtension === 'json') {
                return Reader::readFromJsonFile($filePath);
            } elseif ($fileExtension === 'yml' || $fileExtension === 'yaml') {
                return Reader::readFromYamlFile($filePath);
            }
        } catch (UnresolvableReferenceException) {
            throw new Exception('absolute file path required to resolve references in OpenAPI specifications');
        } catch (Throwable) {
            throw new Exception(sprintf('%s file is not following OpenAPI specifications', $fileExtension));
        }

        throw new Exception('Invalid file type');
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
