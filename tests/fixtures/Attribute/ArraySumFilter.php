<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\Attribute;

use Membrane\Filter;
use Membrane\Result\Result;

class ArraySumFilter implements Filter
{
    public function filter(mixed $value): Result
    {
        return Result::noResult(array_reduce($value, fn($x, $y) => $x + $y));
    }

    public function __toString()
    {
        return 'return a sum of all numbers in given value';
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }
}
