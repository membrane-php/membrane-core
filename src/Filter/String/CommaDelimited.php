<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class CommaDelimited implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('CommaDelimited Filter expects a string value, %s passed instead', [gettype($value)])
                )
            );
        }

        return Result::noResult(explode(',', $value));
    }

    public function __toString()
    {
        return 'seperate comma-delimited string into a list of values';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }
}
