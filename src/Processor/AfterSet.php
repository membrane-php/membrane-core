<?php

namespace Membrane\Processor;

use Membrane\Processor;
use Membrane\Result\Fieldname;
use Membrane\Result\Result;

class AfterSet implements Processor
{
    public function __construct(private Field $field)
    {
    }

    public function processes(): string
    {
        return $this->field->processes();
    }

    public function process(Fieldname $parentFieldname, mixed $value): Result
    {
        return $this->field->process($parentFieldname, $value);
    }
}