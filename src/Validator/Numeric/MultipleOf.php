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
    private readonly float|int $factor;

    public function __construct(float|int $factor)
    {
        if ($factor <= 0) {
            throw new Exception('MultipleOf validator does not support numbers of zero or less');
        }
        $this->factor = $factor;
    }

    public function __toString(): string
    {
        return sprintf('is a multiple of %d', $this->factor);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(%d)', self::class, $this->factor);
    }

    public function validate(mixed $value): Result
    {
        if (!is_numeric($value)) {
            return Result::invalid(
                $value,
                new MessageSet(null, new Message('MultipleOf validator requires a number, %s given', [gettype($value)]))
            );
        }

        if (abs(fmod((float)$value, $this->factor)) === 0.0) {
            return Result::valid($value);
        }

        return Result::invalid(
            $value,
            new MessageSet(
                null,
                new Message('Number is expected to be a multiple of %d', [$this->factor])
            )
        );
    }
}
