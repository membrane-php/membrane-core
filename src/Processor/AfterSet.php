<?php

namespace Membrane\Processor;

use Membrane\Filter;
use Membrane\Processor;
use Membrane\Result\Fieldname;
use Membrane\Result\Result;
use Membrane\Validator;

class AfterSet implements Processor
{
    private readonly Field $field;

    public function __construct(Filter|Validator ...$chain)
    {
        $this->field = new Field('', ...$chain);
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
