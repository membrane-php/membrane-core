<?php

namespace Membrane\Filter\CreateObject;

use Membrane\Filter;
use Membrane\Result\Result;

class WithNamedArguments implements Filter
{
    public function __construct(
        private string $classname
    ) {
    }

    public function filter(mixed $value): Result
    {
        $object = new $this->classname(...$value);
        return new Result($object, Result::NO_RESULT);
    }
}