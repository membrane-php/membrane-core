<?php

declare(strict_types=1);

namespace Membrane\Validator\Collection;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

use function count;
use function gettype;

class Count implements Validator
{
    public function __construct(
        private int $min = 0,
        private ?int $max = null
    ) {
    }

    public function validate(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('Count Validator requires an array, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $count = count($value);

        if ($count < $this->min) {
            $message = new Message('Array is expected have a minimum of %d values', [$this->min]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if ($this->max !== null && $count > $this->max) {
            $message = new Message('Array is expected have a maximum of %d values', [$this->max]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
