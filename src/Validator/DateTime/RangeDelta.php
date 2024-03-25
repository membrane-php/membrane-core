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

    public function __construct(
        private readonly ?DateInterval $minInterval = null,
        private readonly ?DateInterval $maxInterval = null
    ) {
        $this->min = $this->minInterval === null ? null : (new DateTime())->sub($this->minInterval);
        $this->max = $this->maxInterval === null ? null : (new DateTime())->add($this->maxInterval);
    }

    public function __toPHP(): string
    {
        if ($this->minInterval !== null) {
            $minInterval = sprintf(
                'new %s("P%dY%dM%dDT%dH%dM%dS")',
                DateInterval::class,
                $this->minInterval->y,
                $this->minInterval->m,
                $this->minInterval->d,
                $this->minInterval->h,
                $this->minInterval->i,
                $this->minInterval->s,
            );
        }
        if ($this->maxInterval !== null) {
            $maxInterval = sprintf(
                'new %s("P%dY%dM%dDT%dH%dM%dS")',
                DateInterval::class,
                $this->maxInterval->y,
                $this->maxInterval->m,
                $this->maxInterval->d,
                $this->maxInterval->h,
                $this->maxInterval->i,
                $this->maxInterval->s,
            );
        }

        return sprintf('new %s(%s, %s)', self::class, $minInterval ?? 'null', $maxInterval ?? 'null');
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

    public function validate(mixed $value): Result
    {
        if ($this->min !== null && $value < $this->min) {
            $message = new Message('DateTime is expected to be after %s', [$this->min->format(DATE_ATOM)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if ($this->max !== null && $value > $this->max) {
            $message = new Message('DateTime is expected to be before %s', [$this->max->format(DATE_ATOM)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::valid($value);
    }
}
