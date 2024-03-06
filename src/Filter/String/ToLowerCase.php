<?php

declare(strict_types=1);

namespace Membrane\Filter\String;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToLowerCase implements Filter
{
    public function filter(mixed $value): Result
    {
        return is_string($value) ?
            Result::noResult(strtolower($value)) :
            Result::invalid($value, new MessageSet(null, new Message(
                'ToLowerCase Filter expects a string, %s passed instead',
                [gettype($value)]
            )));
    }

    public function __toString(): string
    {
        return 'Convert any string to lower case.';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }
}
