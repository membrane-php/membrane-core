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

    public function __toString(): string
    {
        if ($this->min === null && $this->max === null) {
            return 'will return valid';
        }

        $conditions = [];
        if ($this->min !== null) {
            $conditions[] = sprintf('after %s', $this->min->format('D, d M Y H:i:s'));
        }
        if ($this->max !== null) {
            $conditions[] = sprintf('before %s', $this->max->format('D, d M Y H:i:s'));
        }

        return 'is ' . implode(' and ', $conditions);
    }

    public function __toPHP(): string
    {
        if ($this->min !== null) {
            $min = sprintf('%s::createFromFormat(DATE_ATOM, "%s")', DateTime::class, $this->min->format(DATE_ATOM));
        }
        if ($this->max !== null) {
            $max = sprintf('%s::createFromFormat(DATE_ATOM, "%s")', DateTime::class, $this->max->format(DATE_ATOM));
        }

        return sprintf('new %s(%s, %s)', self::class, $min ?? 'null', $max ?? 'null');
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
