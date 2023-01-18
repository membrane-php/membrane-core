<?php

declare(strict_types=1);

namespace Membrane;

use Membrane\Result\Result;

interface Validator
{
    public function validate(mixed $value): Result;

    public function __toString(): string;

    public function __toPHP(): string;
}
