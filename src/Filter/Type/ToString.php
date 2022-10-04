<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToString implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!(is_object($value) || is_null($value) || is_scalar($value))) {
            $message = new Message(
                'ToString filter only accepts objects, null or scalar values, %s given',
                [gettype($value)]
            );
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            $message = new Message('ToString Filter only accepts objects with __toString method', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult((string) $value);
    }
}
