<?php

declare(strict_types=1);

namespace Membrane;

use Membrane\Result\Result;

interface Filter
{
    public function filter(mixed $value): Result;
}
