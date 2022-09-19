<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToFloat implements Filter
{
    public function filter(mixed $value): Result
    {
        $type = gettype($value);

        if (!(is_scalar($value) || $value === null)) {
            $message = new Message('ToFloat filter only accepts null or scalar values, %s given', [$type]);
            return Result::invalid($value, new MessageSet(null, $message));
        }
        if ($type === 'string' && !is_numeric($value)) {
            $message = new Message('ToFloat filter only accepts numeric strings', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult((float)$value);
    }
}
