<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class QueryStringToArray implements Filter
{
    public function __toString(): string
    {
        return 'convert query string to an array of query parameters';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }

    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('String expected, %s provided', [gettype($value)])
                )
            );
        }

        $parameters = [];
        $segments = array_filter(explode('&', $value), fn($p) => $p !== '');

        foreach ($segments as $segment) {
            $pair = explode('=', $segment);
            $parameters[$pair[0]][] = $pair[1];
        }

        return Result::noResult($parameters);
    }
}
