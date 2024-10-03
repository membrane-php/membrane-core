<?php

declare(strict_types=1);

namespace Membrane\Tests\Fixtures\Filter;

use Membrane\Filter;
use Membrane\Result\Result;

final class ToThis implements Filter
{
    public function filter(mixed $value): Result
    {
        return Result::noResult($this);
    }

    public function __toString(): string
    {
        return sprintf('Return %s', self::class);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s()', self::class);
    }
}
