<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Membrane\Filter\String\JsonDecode;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Result;

class Json implements Processor
{
    private readonly Field $jsonDecode;

    public function __construct(private readonly Processor $wrapped)
    {
        $this->jsonDecode = new Field('', new JsonDecode());
    }

    public function processes(): string
    {
        return $this->wrapped->processes();
    }

    public function process(FieldName $fieldname, mixed $value): Result
    {
        if ($value !== '') {
            $result = $this->jsonDecode->process($fieldname, $value);

            if (!$result->isValid()) {
                return $result;
            }
        }

        return $this->wrapped->process($fieldname, $result->value ?? $value);
    }
}
