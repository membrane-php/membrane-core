<?php

declare(strict_types=1);

namespace Membrane\Filter\Shape;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Delete implements Filter
{
    /** @var string[] */
    private readonly array $fieldNames;

    public function __construct(string ...$fieldNames)
    {
        $this->fieldNames = $fieldNames;
    }

    public function __toString(): string
    {
        if ($this->fieldNames === []) {
            return '';
        }

        return sprintf('delete "' . implode('", "', $this->fieldNames) . '" from self');
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(', self::class) .
            implode(', ', array_map(fn($p) => '"' . $p . '"', $this->fieldNames)) .
            ')';
    }

    public function filter(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('Delete filter requires arrays, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (array_is_list($value)) {
            $message = new Message('Delete filter requires arrays, for lists use Truncate', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        foreach ($this->fieldNames as $fieldName) {
            unset($value[$fieldName]);
        }

        return Result::noResult($value);
    }
}
