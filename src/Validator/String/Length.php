<?php

declare(strict_types=1);

namespace Membrane\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Length implements Validator
{
    public function __construct(
        private int $min = 0,
        private ?int $max = null
    ) {
    }

    public function __toString(): string
    {
        if ($this->min === 0 && $this->max === null) {
            return 'will return valid';
        }

        $conditions = [];
        if ($this->min > 0) {
            $conditions[] = sprintf('is %d characters or more', $this->min);
        }
        if ($this->max !== null) {
            $conditions[] = sprintf('is %d characters or less', $this->max);
        }

        return implode(' and ', $conditions);
    }

    public function validate(mixed $value): Result
    {
        if (!is_string($value)) {
            $message = new Message('Length Validator requires a string, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $length = strlen($value);

        if ($length < $this->min) {
            $message = new Message('String is expected to be a minimum of %d characters', [$this->min]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if ($this->max !== null && $length > $this->max) {
            $message = new Message('String is expected to be a maximum of %d characters', [$this->max]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
