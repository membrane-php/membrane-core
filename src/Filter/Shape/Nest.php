<?php

declare(strict_types=1);

namespace Membrane\Filter\Shape;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Nest implements Filter
{
    /** @var string[] */
    private array $fields;

    public function __construct(
        private string $newField,
        string ...$fields
    ) {
        $this->fields = $fields;
    }

    public function filter(mixed $value): Result
    {
        if (!is_array($value)) {
            return new Result(
                $value,
                Result::INVALID,
                new MessageSet(
                    null,
                    new Message(
                        'Value passed to Nest filter must be an array, %s passed instead',
                        [gettype($value)]
                    )
                )
            );
        }

        if (array_is_list($value)) {
            return new Result(
                $value,
                Result::INVALID,
                new MessageSet(
                    null,
                    new Message(
                        'Value passed to Nest filter was a list, this filter needs string keys to work',
                        []
                    )
                )
            );
        }

        $newValue = [];
        $collected = [];

        foreach ($value as $key => $item) {
            if (in_array($key, $this->fields)) {
                $collected[$key] = $item;
            } else {
                $newValue[$key] = $item;
            }
        }

        $newValue[$this->newField] = $collected;

        return new Result($newValue, Result::NO_RESULT);
    }
}
