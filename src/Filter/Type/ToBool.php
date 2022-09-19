<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToBool implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_scalar($value)) {
            $message = new Message('ToBool filter only accepts scalar values, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            $message = new Message('ToBool filter failed to convert value to boolean', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult($bool);
    }
}
