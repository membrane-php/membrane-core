<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class AlphaNumeric implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_string($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('AlphaNumeric Filter expects a string value, %s passed instead', [gettype($value)])
                )
            );
        }

        $filteredValue = preg_replace('#[^a-zA-Z0-9]#', '', $value);

        return Result::noResult($filteredValue);
    }

    public function __toString(): string
    {
        return 'Remove all non-alphanumeric characters';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }
}
