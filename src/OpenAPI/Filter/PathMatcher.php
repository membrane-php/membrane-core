<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter;

use Exception;
use Membrane\Filter;
use Membrane\OpenAPI\PathMatcher as PathMatcherClass;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class PathMatcher implements Filter
{
    public function __construct(private readonly PathMatcherClass $pathMatcher)
    {
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
        } catch (Exception) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('requestPath does not match expected pattern', []))
            );
        }

        return Result::noResult($pathParams);
    }
}
