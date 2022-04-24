<?php

namespace Membrane\Validator\String;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Length implements Validator
{
    public function __construct(
        private ?int $min = null,
        private ?int $max = null
    ){
    }

    public function validate(mixed $value): Result
    {
        $length = strlen($value);

        if ($this->min !== null && $length < $this->min) {
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