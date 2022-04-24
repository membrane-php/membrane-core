<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Processor;
use Membrane\Result\Fieldname;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Fieldset implements Processor
{
    private array $chain;
    private Processor $before;
    private Processor $after;

    public function __construct(
        private readonly string $processes,
        Processor ...$chain
    ) {
        foreach ($chain as $item) {
            if ($item instanceof BeforeSet) {
                $this->before = $item;
            }

            if ($item instanceof AfterSet) {
                $this->after = $item;
            }
        }
        $this->chain = $chain;
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(Fieldname $parentFieldname, mixed $value): Result
    {
        $fieldname = $parentFieldname->push(new Fieldname($this->processes));
        $fieldsetResult = Result::noResult($value);

        if (isset($this->before)) {
            $result = $this->before->process($fieldname, $value);
            $value = $result->value;
            $fieldsetResult = $fieldsetResult->merge($result);

            if (!$fieldsetResult->isValid()){
                return $fieldsetResult;
            }
        }

        foreach ($this->chain as $item) {
            $processes = $item->processes();
            if (array_key_exists($processes, $value)) {
                $result = $item->process($fieldname, $value[$processes]);
                $value[$processes] = $result->value;
                $fieldsetResult = $fieldsetResult->merge($result);
            }
        }

        if (isset($this->after) && $fieldsetResult->isValid()) {
            $result = $this->after->process($fieldname, $value);
            $fieldsetResult = $fieldsetResult->merge($result);

            if (!$fieldsetResult->isValid()){
                return $fieldsetResult;
            }
        }

        return $fieldsetResult->merge(Result::noResult($value));
    }
}