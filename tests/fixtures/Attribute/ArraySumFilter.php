<?php

declare(strict_types=1);

namespace Membrane\Fixtures\Attribute;

use Membrane\Filter;
use Membrane\Result\Result;

class ArraySumFilter implements Filter
{
    public function filter(mixed $value): Result
    {
        return Result::noResult(array_reduce($value, fn($x, $y) => $x + $y));
    }
}
