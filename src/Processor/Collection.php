<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Processor;
use Membrane\Result\Fieldname;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class Collection implements Processor
{
    private Processor $each;
    private Processor $before;
    private Processor $after;

    public function __construct(
        private readonly string $processes,
        Processor ...$chain
    ) {
        $processors = $chain;
        foreach ($chain as $k => $item) {
            if ($item instanceof BeforeSet) {
                $this->before = $item;
                unset($processors[$k]);
            }

            if ($item instanceof AfterSet) {
                $this->after = $item;
                unset($processors[$k]);
            }
        }

        if (count($processors) > 1) {
            throw new \RuntimeException('Cannot use more than one processor on a collection');
        }

        $each = current($processors);
        if ($each instanceof Processor) {
            $this->each = $each;
        }
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(Fieldname $parentFieldname, mixed $value): Result
    {
        //@TODO ensure it's an array?
        $fieldname = $parentFieldname->push(new Fieldname($this->processes));
        $collectionResult = Result::noResult($value);

        if (isset($this->before)) {
            $result = $this->before->process($fieldname, $value);
            $value = $result->value;
            $collectionResult = $collectionResult->merge($result);

            if (!$collectionResult->isValid()){
                return $collectionResult;
            }
        }

        $processedValues = [];
        foreach ($value as $key => $item) {
            $result = $this->each->process($fieldname->push(new Fieldname((string)$key)), $item);
            $processedValues[$key] = $result->value;
            $collectionResult = $collectionResult->merge($result);
        }

        if (isset($this->after) && $collectionResult->isValid()) {
            $result = $this->after->process($fieldname, $processedValues);
            $collectionResult = $collectionResult->merge($result);

            if (!$collectionResult->isValid()){
                return $collectionResult;
            }
        }

        return $collectionResult->merge(Result::noResult($processedValues));
    }
}