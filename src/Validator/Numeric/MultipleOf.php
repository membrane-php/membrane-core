<?php

declare(strict_types=1);

namespace Membrane\Validator\Numeric;

use Exception;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class MultipleOf implements Validator
{
    private readonly float|int $multiple;

    public function __construct(float|int $multiple)
    {
        if ($multiple <= 0) {
            throw new Exception('MultipleOf validator does not support numbers of zero or less');
        }
        $this->multiple = $multiple;
    }

    public function validate(mixed $value): Result
    {
        if (fmod($value, $this->multiple) == 0) {
            return Result::valid($value);
        }

        return Result::invalid(
            $value,
            new MessageSet(
                null,
                new Message('Number is expected to be a multiple of %d', [$this->multiple])
            )
        );
    }
}
