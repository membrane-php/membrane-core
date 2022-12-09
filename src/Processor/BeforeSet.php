<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Filter;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Result;
use Membrane\Validator;

class BeforeSet implements Processor
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

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        return $this->field->process($parentFieldName, $value);
    }
}
