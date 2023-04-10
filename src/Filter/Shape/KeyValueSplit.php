<?php

declare(strict_types=1);

namespace Membrane\Filter\Shape;

use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class KeyValueSplit implements \Membrane\Filter
{
    public function __construct(
        private readonly bool $keysFirst = true
    ) {
    }

    public function filter(mixed $value): Result
    {
        if (!is_array($value) || !array_is_list($value)) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('KeyValueSplit Filter expects a list value, %s passed instead', [gettype($value)])
                )
            );
        }

        if (count($value) % 2 !== 0) {
            return Result::invalid(
                $value,
                new MessageSet(
                    null,
                    new Message('KeyValueSplit requires a list with an even number of values', [])
                )
            );
        }

        $first = array_filter($value, fn($key) => $key % 2 === 0, ARRAY_FILTER_USE_KEY);
        $second = array_filter($value, fn($key) => $key % 2 !== 0, ARRAY_FILTER_USE_KEY);

        $filteredValue = $this->keysFirst ? array_combine($first, $second) : array_combine($second, $first);

        return Result::valid($filteredValue);
    }

    public function __toString()
    {
        return 'split list into keys and values, then combine them into an array';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(%s)', self::class, $this->keysFirst ? 'true' : 'false');
    }
}
