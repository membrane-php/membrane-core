<?php

declare(strict_types=1);

namespace Membrane\Result;

class Result
{
    public const VALID = 1;
    public const NO_RESULT = 0;
    public const INVALID = -1;

    /** @var MessageSet[] */
    public readonly array $messageSets;

    public function __construct(
        public readonly mixed $value,
        public readonly int $result,
        MessageSet ...$messageSets,
    ) {
        $this->messageSets = $messageSets;
    }

    public static function valid(mixed $value): self
    {
        return new self($value, Result::VALID);
    }

    public static function invalid(mixed $value, MessageSet ...$messageSets): self
    {
        return new self($value, Result::INVALID, ...$messageSets);
    }

    public static function noResult(mixed $value): self
    {
        return new self($value, Result::NO_RESULT);
    }

    public function merge(Result $result): Result
    {
        return new Result(
            $result->value,
            $this->mergeResult($result),
            ...$this->messageSets,
            ...$result->messageSets
        );
    }

    public function isValid(): bool
    {
        return $this->result >= 0;
    }

    private function mergeResult(Result $result): int
    {
        if ($result->result === self::NO_RESULT) {
            return $this->result;
        }

        return $result->isValid() && $this->isValid() ? self::VALID : self::INVALID;
    }
}
