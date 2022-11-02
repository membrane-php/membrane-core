<?php

declare(strict_types=1);

namespace Membrane\Validator\Numeric;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Minimum implements Validator
{
    public function __construct(
        private readonly float|int $min,
        private readonly bool $exclusive = false
    ) {
    }

    public function validate(mixed $value): Result
    {
        if (!is_numeric($value)) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('Minimum validator requires a number, %s given', [gettype($value)]))
            );
        }

        if ($value < $this->min || ($this->exclusive && (float)$value === (float)$this->min)) {
            $message = $this->exclusive ?
                'Number has an exclusive minimum of %d'
                :
                'Number has an inclusive minimum of %d';
            return Result::invalid($value, new MessageSet(null, new Message($message, [$this->min])));
        }

        return Result::valid($value);
    }
}
