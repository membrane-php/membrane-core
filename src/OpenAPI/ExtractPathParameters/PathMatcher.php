<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\ExtractPathParameters;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;

class PathMatcher implements ExtractsPathParameters
{
    private readonly string $regex;
    /** @var string[] */
    private readonly array $parameters;
    private readonly string $pathUrl;

    public function __construct(
        public readonly string $serverUrl,
        public readonly string $apiPath
    ) {
        $parseUrl = parse_url($this->serverUrl, PHP_URL_PATH);
        $this->pathUrl = is_string($parseUrl) ? $parseUrl : '';

        $parameterNames = [];
        $pregParts = [];
        $inParameter = false;

        $parts = preg_split('#([{}])#', $this->apiPath, -1, PREG_SPLIT_DELIM_CAPTURE);
        assert($parts !== false);
        foreach ($parts as $part) {
            switch ($part) {
                case '{':
                    $inParameter = !$inParameter ? true :
                    throw CannotProcessOpenAPI::invalidPath($this->apiPath);
                    continue 2;
                case '}':
                    $inParameter = $inParameter ? false :
                    throw CannotProcessOpenAPI::invalidPath($this->apiPath);
                    continue 2;
            }

            if ($inParameter) {
                $pregParts[] = '(?<' . $part . '>[^/]+)';
                $parameterNames[] = $part;
            } else {
                $pregParts[] = preg_quote($part, '#');
            }
        }

        $this->regex = '#^' . implode($pregParts) . '$#';
        $this->parameters = $parameterNames;
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s", "%s")', self::class, $this->serverUrl, $this->apiPath);
    }

    public function matches(string $requestPath): bool
    {
        $requestPath = $this->removeServerFromPath($requestPath);
        return preg_match($this->regex, $requestPath) === 1;
    }

    /** @return array<string, string> */
    public function getPathParams(string $requestPath): array
    {
        if (!$this->matches($requestPath)) {
            throw CannotProcessOpenAPI::mismatchedPath($this->regex, $requestPath);
        }

        $requestPath = $this->removeServerFromPath($requestPath);

        $parameters = [];
        $requestPath = strtok($requestPath, '?');
        assert(is_string($requestPath));

        preg_match($this->regex, $requestPath, $parameters);

        return array_filter($parameters, fn($key) => in_array($key, $this->parameters), ARRAY_FILTER_USE_KEY);
    }

    private function removeServerFromPath(string $requestPath): string
    {
        $parseUrl = parse_url($requestPath, PHP_URL_PATH);
        $requestPath = is_string($parseUrl) ? $parseUrl : $requestPath;

        return substr($requestPath, strlen($this->pathUrl));
    }
}
