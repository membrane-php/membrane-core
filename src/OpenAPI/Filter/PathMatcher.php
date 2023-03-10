<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter;

use Membrane\Filter;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\ExtractPathParameters\ExtractsPathParameters;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class PathMatcher implements Filter
{
    public function __construct(private readonly ExtractsPathParameters $pathMatcher)
    {
    }

    public function __toString(): string
    {
        return 'convert url to a field set of path parameters';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(%s)', self::class, $this->pathMatcher->__toPHP());
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('PathMatcher filter expects string, %s passed', [gettype($value)]))
            );
        }

        try {
            $pathParams = $this->pathMatcher->getPathParams($value);
        } catch (CannotProcessOpenAPI) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('requestPath does not match expected pattern', []))
            );
        }

        return Result::noResult($pathParams);
    }
}
