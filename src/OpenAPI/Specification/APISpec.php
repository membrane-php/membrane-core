<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Specification;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\Reader;
use cebe\openapi\spec\MediaType;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Schema;
use Exception;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotReadOpenAPI;
use Membrane\OpenAPI\Exception\InvalidOpenAPI;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\PathMatcher;
use Symfony\Component\Yaml\Exception\ParseException;
use TypeError;

use function str_starts_with;

abstract class APISpec implements Specification
{
    public readonly PathItem $pathItem;
    public readonly PathMatcher $matchingPath;

    // @TODO support alternative servers found in both Path or PathItem objects

    public function __construct(string $filePath, string $url)
    {
        $openAPI = $this->readAPIFile($filePath);
        $openAPI->validate() ?: throw InvalidOpenAPI::invalidOpenAPI(pathinfo($filePath, PATHINFO_BASENAME));

        $serverUrl = $this->matchServer($openAPI, $url);
        foreach ($openAPI->paths->getPaths() as $path => $pathItem) {
            $pathMatcher = new PathMatcher($serverUrl, $path);
            if ($pathMatcher->matches($url)) {
                $this->matchingPath = $pathMatcher;
                $this->pathItem = $pathItem;
                break;
            }
        }

            $this->matchingPath ?? throw CannotReadOpenAPI::pathNotFound(pathinfo($filePath, PATHINFO_BASENAME), $url);
    }

    protected function getOperation(Method $method): Operation
    {
        return $this->pathItem->getOperations()[$method->value]
            ??
            throw new Exception(sprintf('%s method not specified on path', $method->value));
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


    private function readAPIFile(string $filePath): OpenApi
    {
        if (!file_exists($filePath)) {
            throw CannotReadOpenAPI::fileNotFound($filePath);
        }

        $fileExtension = pathinfo(strtolower($filePath), PATHINFO_EXTENSION);
        try {
            if ($fileExtension === 'json') {
                return Reader::readFromJsonFile($filePath);
            } elseif ($fileExtension === 'yml' || $fileExtension === 'yaml') {
                return Reader::readFromYamlFile($filePath);
            }
            /** @phpstan-ignore-next-line Missing annotation on underlying library */
        } catch (TypeError | ParseException) {
            throw CannotReadOpenAPI::unsupportedFormat(pathinfo($filePath, PATHINFO_BASENAME));
        } catch (UnresolvableReferenceException $e) {
            throw CannotReadOpenAPI::unresolvedReference(pathinfo($filePath, PATHINFO_BASENAME), $e);
        } catch (TypeErrorException $e) {
            throw InvalidOpenAPI::invalidSpecData($e);
        }

        throw CannotReadOpenAPI::unsupportedFileType(pathinfo($filePath, PATHINFO_EXTENSION));
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
