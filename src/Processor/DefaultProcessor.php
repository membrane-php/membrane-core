<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Filter;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Result;
use Membrane\Validator;

class DefaultProcessor implements Processor
{
    public function __construct(
        private readonly Processor $processor
    ) {
    }

    public static function fromFiltersAndValidators(Filter|Validator ...$chain): self
    {
        return new self(new Field('', ...$chain));
    }

    public function __toString(): string
    {
        return (string)$this->processor;
    }

    public function processes(): string
    {
        return $this->processor->processes();
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        return $this->processor->process($parentFieldName, $value);
    }
}
