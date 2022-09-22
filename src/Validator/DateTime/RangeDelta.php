<?php

declare(strict_types=1);

namespace Membrane\Validator\DateTime;

use DateInterval;
use DateTime;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class RangeDelta implements Validator
{
    private ?DateTime $min;
    private ?DateTime $max;

    public function __construct(?DateInterval $min = null, ?DateInterval $max = null)
    {
        $this->min = $min === null ? null : (new DateTime())->sub($min);
        $this->max = $max === null ? null : (new DateTime())->add($max);
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
