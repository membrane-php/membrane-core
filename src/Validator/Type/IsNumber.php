<?php

declare(strict_types=1);

namespace Membrane\Validator\Type;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class IsNumber implements Validator
{
    public function validate(mixed $value): Result
    {
        if (!(is_int($value) || is_float($value))) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('Value must be a number, %s passed', [gettype($value)]))
            );
        }

        return Result::valid($value);
    }
}
