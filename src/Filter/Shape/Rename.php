<?php

declare(strict_types=1);

namespace Membrane\Filter\Shape;

use Membrane\Filter;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use RuntimeException;

class Rename implements Filter
{
    private readonly string $old;
    private readonly string $new;

    public function __construct(string $old, string $new)
    {
        if ($old === $new) {
            throw new RuntimeException('Rename filter does not accept two equal strings');
        }
        $this->old = $old;
        $this->new = $new;
    }

    public function filter(mixed $value): Result
    {
        if (!is_array($value)) {
            $message = new Message('Rename filter requires arrays, %s given', [gettype($value)]);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (array_is_list($value)) {
            $message = new Message('Rename filter requires arrays with key-value pairs', []);
            return Result::invalid($value, new MessageSet(null, $message));
        }

        if (isset($value[$this->old])) {
            $value[$this->new] = $value[$this->old];
            unset($value[$this->old]);
        }

        return Result::noResult($value);
    }
}
