<?php

declare(strict_types=1);

namespace Membrane\Validator\DateTime;

use DateTime;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Range implements Validator
{
    public function __construct(
        private ?DateTime $min = null,
        private ?DateTime $max = null
    ) {
    }

    public function validate(mixed $value): Result
    {
        if ($this->min !== null && $value < $this->min) {
            $message = new Message('DateTime is expected to be after %s', [$this->min]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if ($this->max !== null && $value > $this->max) {
            $message = new Message('DateTime is expected to be before %s', [$this->max]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
