<?php

declare(strict_types=1);

namespace Membrane\Validator\Numeric;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Range implements Validator
{
    public function __construct(
        private int|float|null $min = null,
        private int|float|null $max = null
    )
    {
    }

    public function validate(mixed $value): Result
    {
        if ($this->min !== null && $value < $this->min) {
            $message = new Message('Number is expected to be a minimum of %d', [$this->min]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if ($this->max !== null && $value > $this->max) {
            $message = new Message('Number is expected to be a maximum of %d', [$this->max]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
