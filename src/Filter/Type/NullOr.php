<?php

declare(strict_types=1);

namespace Membrane\Filter\Type;

use Membrane\Filter;
use Membrane\Result\Result;

final class NullOr implements Filter
{
    public function __construct(
        private readonly Filter $alternativeFilter,
    ) {
    }

    public function __toString(): string
    {
        return sprintf('Accept null or %s', $this->alternativeFilter);
    }

    public function __toPHP(): string
    {
        return sprintf(
            'new %s(%s)',
            self::class,
            $this->alternativeFilter->__toPHP(),
        );
    }

    public function filter(mixed $value): Result
    {
        return $value === null ?
            Result::noResult(null) :
            $this->alternativeFilter->filter($value);
    }
}
