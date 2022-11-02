<?php

declare(strict_types=1);

namespace Membrane\Validator\Numeric;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Maximum implements Validator
{
    public function __construct(
        private readonly float|int $max,
        private readonly bool $exclusive = false
    ) {
    }

    public function validate(mixed $value): Result
    {
        if (!is_numeric($value)) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('Maximum validator requires a number, %s given', [gettype($value)]))
            );
        }

        if ($value > $this->max || ($this->exclusive && (float)$value === (float)$this->max)) {
            $message = $this->exclusive ?
                'Number has an exclusive maximum of %d'
                :
                'Number has an inclusive maximum of %d';
            return Result::invalid($value, new MessageSet(null, new Message($message, [$this->max])));
        }

        return Result::valid($value);
    }
}
