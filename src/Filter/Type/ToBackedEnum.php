<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

final class ToBackedEnum implements Filter
{
    /** @param class-string $className */
    public function __construct(
        private string $className
    ) {
        if (
            !enum_exists($this->className)
            || !in_array(\BackedEnum::class, class_implements($this->className))
        ) {
            throw new \RuntimeException(
                "{$this->className} is not a backed enum, or does not exist."
            );
        }
    }

    public function filter(mixed $value): Result
    {
        if (!is_int($value) && !is_string($value)) {
            return Result::invalid($value, new MessageSet(null, new Message(
                'ToBackedEnum accepts int|string values only, %s given',
                [gettype($value)],
            )));
        }

        try {
            return Result::noResult($this->className::from($value));
        } catch (\TypeError) {
            return Result::invalid($value, new MessageSet(null, new Message(
                '%s value does not match backing type of %s',
                [gettype($value), $this->className],
            )));
        } catch (\ValueError) {
            return Result::invalid($value, new MessageSet(null, new Message(
                'value does not match a case of %s',
                [$this->className],
            )));
        }
    }

    public function __toString(): string
    {
        return "convert to {$this->className}";
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(\'%s\')', self::class, $this->className);
    }
}
