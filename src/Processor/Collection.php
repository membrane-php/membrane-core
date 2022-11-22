<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
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
            throw InvalidProcessorArguments::multipleProcessorsInCollection();
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

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $fieldName = $parentFieldName->push(new Fieldname($this->processes));
        $collectionResult = Result::noResult($value);

        if (isset($this->before)) {
            $result = $this->before->process($fieldName, $value);
            $value = $result->value;
            $collectionResult = $collectionResult->merge($result);

            if (!$collectionResult->isValid()) {
                return $collectionResult;
            }
        }

        if (isset($this->each)) {
            if (!(is_array($value) && array_is_list($value))) {
                return Result::invalid(
                    $value,
                    new MessageSet(
                        null,
                        new Message(
                            'Value passed to %s in Collection chain must be a list, %s passed instead',
                            [$this->each::class, gettype($value)]
                        )
                    )
                );
            }

            $processedValues = [];

            foreach ($value as $key => $item) {
                $result = $this->each->process($fieldName->push(new Fieldname((string)$key)), $item);
                $processedValues[$key] = $result->value;
                $collectionResult = $collectionResult->merge($result);
            }

            $collectionResult = $collectionResult->merge(Result::noResult($processedValues));
        }

        if (isset($this->after) && $collectionResult->isValid()) {
            $result = $this->after->process($fieldName, $collectionResult->value);
            $collectionResult = $collectionResult->merge($result);

            if (!$collectionResult->isValid()) {
                return $collectionResult;
            }
        }

        return $collectionResult;
    }
}
