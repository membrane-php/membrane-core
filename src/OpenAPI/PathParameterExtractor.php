<?php

declare(strict_types=1);

namespace Membrane\OpenAPI;

use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;

class PathParameterExtractor implements Builder\ExtractsPathParameters
{
    private readonly string $regex;
    /** @var string[] */
    private readonly array $parameters;

    public function __construct(
        string $relativeUrl
    ) {
        $parameterNames = [];
        $pregParts = [];
        $inParameter = false;

        $parts = preg_split('#([{}])#', $relativeUrl, -1, PREG_SPLIT_DELIM_CAPTURE);
        assert($parts !== false);
        foreach ($parts as $part) {
            switch ($part) {
                case '{':
                    if ($inParameter) {
                        throw CannotProcessOpenAPI::invalidPath($relativeUrl);
                    }
                    $inParameter = true;
                    continue 2;
                case '}':
                    if (!$inParameter) {
                        throw CannotProcessOpenAPI::invalidPath($relativeUrl);
                    }
                    $inParameter = false;
                    continue 2;
            }

            if ($inParameter) {
                $pregParts[] = '(?<' . $part . '>[^/]+)';
                $parameterNames[] = $part;
            } else {
                $pregParts[] = preg_quote($part, '#');
            }
        }

        $this->regex = '#' . implode($pregParts) . '$#';
        $this->parameters = $parameterNames;
    }

    /** @return array<string, string> */
    public function getPathParams(string $requestPath): array
    {
        $parameters = [];
        $requestPath = strtok($requestPath, '?');
        assert(is_string($requestPath));

        preg_match($this->regex, $requestPath, $parameters);

        return array_filter($parameters, fn($key) => in_array($key, $this->parameters), ARRAY_FILTER_USE_KEY);
    }
}
