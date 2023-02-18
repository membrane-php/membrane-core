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
    /** @var Filter[]|Validator[] */
    private readonly array $chain;
    private readonly Field $field;

    public function __construct(Filter|Validator ...$chain)
    {
        $this->chain = $chain;
        $this->field = new Field('', ...$this->chain);
    }

    public function __toPHP(): string
    {
        return sprintf('new %s(%s)', self::class, implode(', ', array_map(fn($p) => $p->__toPHP(), $this->chain)));
    }

    public function __toString(): string
    {
        return (string)$this->field;
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
