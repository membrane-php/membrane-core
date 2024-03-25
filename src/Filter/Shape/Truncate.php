<?php

declare(strict_types=1);

namespace Membrane\Filter\Shape;

use Exception;
use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Truncate implements Filter
{
    public function __construct(private readonly int $maxLength)
    {
        if ($this->maxLength < 0) {
            throw new Exception('Truncate filter cannot take negative max lengths');
        }
    }

    public function __toString(): string
    {
        return sprintf('truncate self to %d fields or less', $this->maxLength);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(%d)', self::class, $this->maxLength);
    }

    public function filter(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('Truncate filter requires lists, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }
        if (!array_is_list($value)) {
            $message = new Message('Truncate filter requires lists, for arrays use Delete', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        return Result::noResult(array_slice($value, 0, $this->maxLength));
    }
}
