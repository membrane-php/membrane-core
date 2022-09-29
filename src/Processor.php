<?php

declare(strict_types=1);

namespace Membrane;

use Membrane\Result\FieldName;
use Membrane\Result\Result;

interface Processor
{
    public function processes(): string;

    public function process(FieldName $parentFieldName, mixed $value): Result;
}
