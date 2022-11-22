<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Filter;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class HTTPParameters implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('HTTPParameters expects string value, %s passed instead', [gettype($value)])
                )
            );
        }

        $parameters = [];
        parse_str($value, $parameters);
        return Result::valid($parameters);
    }
}
