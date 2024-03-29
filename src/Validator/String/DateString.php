<?php

declare(strict_types=1);

namespace Membrane\Validator\String;

use DateTime;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class DateString implements Validator
{
    public function __construct(private readonly string $format, private readonly bool $strict = false)
    {
    }

    public function __toString(): string
    {
        return sprintf('matches the DateTime format: "%s"', $this->format);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s("%s", %s)', self::class, $this->format, $this->strict ? 'true' : 'false');
    }

    public function validate(mixed $value): Result
    {
        if (!is_string($value)) {
            $message = new Message('DateString Validator requires a string, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $dateTime = DateTime::createFromFormat($this->format, $value);

        if ($dateTime === false) {
            $message = new Message('String does not match the required format %s', [$this->format]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if ($this->strict && $value !== $dateTime->format($this->format)) {
            $message = new Message('String does not represent a valid date in format %s', [$this->format]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
