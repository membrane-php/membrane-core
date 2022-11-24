<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class ToNumber implements Filter
{
    public function filter(mixed $value): Result
    {
        if (!is_numeric($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('ToNumber filter expects numeric values, %s passed', [gettype($value)])
                )
            );
        }

        $value = (string)$value === (string)(int)$value ? (int)$value : (double)$value;

        return Result::valid($value);
    }
}
