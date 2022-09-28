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
        $type = gettype($value);
        if (!($type === 'object' || $value === null || is_scalar($value))) {
            $message = new Message('ToString filter only accepts objects, null or scalar values, %s given', [$type]);
            return Result::invalid($value, new MessageSet(null, $message));
        }
        if ($type === 'object' && !method_exists($value, '__toString')) {
            $message = new Message('ToString Filter only accepts objects with __toString method', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult((string)$value);
    }
}
