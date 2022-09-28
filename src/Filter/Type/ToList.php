<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToList implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('ToList filter only accepts arrays, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult(array_values($value));
    }
}
