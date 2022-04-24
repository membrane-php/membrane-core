<?php

declare(strict_types=1);

namespace Membrane;

use Membrane\Result\Fieldname;
use Membrane\Result\Result;

interface Processor
{
    public function processes(): string;
    public function process(Fieldname $parentFieldname, mixed $value): Result;
}