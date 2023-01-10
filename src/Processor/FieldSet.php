<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Exception\InvalidProcessorArguments;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;

class FieldSet implements Processor
{
    /** @var Processor[][] */
    private array $chain = [];
    private Processor $before;
    private Processor $after;
    private Processor $default;

    public function __construct(
        private readonly string $processes,
        Processor ...$chain
    ) {
        foreach ($chain as $item) {
            if ($item instanceof BeforeSet) {
                if (isset($this->before)) {
                    throw InvalidProcessorArguments::multipleBeforeSetsInFieldSet();
                }
                $this->before = $item;
            } elseif ($item instanceof AfterSet) {
                if (isset($this->after)) {
                    throw InvalidProcessorArguments::multipleAfterSetsInFieldSet();
                }
                $this->after = $item;
            } elseif ($item instanceof DefaultProcessor) {
                if (isset($this->default)) {
                    throw InvalidProcessorArguments::multipleDefaultProcessorsInFieldSet();
                }
                $this->default = $item;
            } else {
                $this->chain[$item->processes()][] = $item;
            }
        }
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $fieldName = $parentFieldName->push(new Fieldname($this->processes));
        $fieldSetResult = Result::noResult($value);

        if (isset($this->before)) {
            $this->handleProcessor($this->before, $fieldName, $value, $fieldSetResult);

            if (!$fieldSetResult->isValid()) {
                return $fieldSetResult;
            }
        }

        if (!empty($this->chain)) {
            if (!is_array($value)) {
                return Result::invalid(
                    $value,
                    new MessageSet(
                        null,
                        new Message('Value passed to FieldSet chain be an array, %s passed instead', [gettype($value)])
                    )
                );
            }
            if (array_is_list($value) && $value !== []) {
                return Result::invalid(
                    $value,
                    new MessageSet(
                        null,
                        new Message('Value passed to FieldSet chain must be an array, list passed instead', [])
                    )
                );
            }

            foreach ($value as $fieldKey => $field) {
                if (array_key_exists($fieldKey, $this->chain)) {
                    foreach ($this->chain[$fieldKey] as $processor) {
                        $this->handleProcessor($processor, $fieldName, $value[$fieldKey], $fieldSetResult);
                    }
                } elseif (isset($this->default)) {
                    $this->handleProcessor($this->default, $fieldName, $value[$fieldKey], $fieldSetResult);
                }
            }
        }

        $fieldSetResult = $fieldSetResult->merge(Result::noResult($value));

        if (isset($this->after) && $fieldSetResult->isValid()) {
            $this->handleProcessor($this->after, $fieldName, $value, $fieldSetResult);

            if (!$fieldSetResult->isValid()) {
                return $fieldSetResult;
            }
        }

        return $fieldSetResult;
    }

    private function handleProcessor(
        Processor $processor,
        FieldName $fieldName,
        mixed &$value,
        Result &$fieldSetResult
    ): void {
        $result = $processor->process($fieldName, $value);
        $value = $result->value;
        $fieldSetResult = $fieldSetResult->merge($result);
    }
}
