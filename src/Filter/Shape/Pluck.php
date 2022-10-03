<?php

declare(strict_types=1);

namespace Membrane\Filter\Shape;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Pluck implements Filter
{
    private readonly string $fieldSet;
    /** @var string[] */
    private readonly array $fieldNames;

    public function __construct(string $fieldSet, string ...$fieldNames)
    {
        $this->fieldSet = $fieldSet;
        $this->fieldNames = $fieldNames;
    }

    public function filter(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('Pluck filter requires arrays, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (array_is_list($value)) {
            $message = new Message('Pluck filter requires arrays with key-value pairs', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        $from = $value[$this->fieldSet] ?? [];
        if (is_array($from) && !array_is_list($from)) {
            foreach ($this->fieldNames as $fieldName) {
                if (isset($from[$fieldName])) {
                    $fieldNameValue = $from[$fieldName];
                    $value[$fieldName] = $fieldNameValue;
                }
            }
        }

        return Result::noResult($value);
    }
}
