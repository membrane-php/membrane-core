<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use RuntimeException;

class FieldSet implements Processor
{
    /** @var mixed[] */
    private array $chain = [];
    private Processor $before;
    private Processor $after;

    public function __construct(
        private readonly string $processes,
        Processor ...$chain
    ) {
        foreach ($chain as $item) {
            if ($item instanceof BeforeSet) {
                if (isset($this->before)) {
                    throw (new RuntimeException('Only allowed one BeforeSet'));
                }
                $this->before = $item;
            } elseif ($item instanceof AfterSet) {
                if (isset($this->after)) {
                    throw (new RuntimeException('Only allowed one AfterSet'));
                }
                $this->after = $item;
            } else {
                $this->chain[] = $item;
            }
        }
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $fieldName = $parentFieldName->push(new FieldName($this->processes));

        if (!is_array($value)) {
            return Result::invalid($value, new MessageSet(
                null,
                new Message('Value passed to FieldSet must be an array, %s passed instead', [gettype($value)])
            ));
        }

        if (array_is_list($value) && $value !== []) {
            return Result::invalid($value, new MessageSet(
                null,
                new Message('Value passed to FieldSet must be an array, list passed instead', [])
            ));
        }

        $fieldName = $parentFieldName->push(new Fieldname($this->processes));
        $fieldSetResult = Result::noResult($value);

        if (isset($this->before)) {
            $result = $this->before->process($fieldName, $value);
            $value = $result->value;
            $fieldSetResult = $fieldSetResult->merge($result);

            if (!$fieldSetResult->isValid()) {
                return $fieldSetResult;
            }
        }

        foreach ($this->chain as $item) {
            $processes = $item->processes();
            if (array_key_exists($processes, $value)) {
                $result = $item->process($fieldName, $value[$processes]);
                $value[$processes] = $result->value;
                $fieldSetResult = $fieldSetResult->merge($result);
            }
        }

        $fieldSetResult = $fieldSetResult->merge(Result::noResult($value));

        if (isset($this->after) && $fieldSetResult->isValid()) {
            $result = $this->after->process($fieldName, $value);
            $fieldSetResult = $fieldSetResult->merge($result);

            if (!$fieldSetResult->isValid()) {
                return $fieldSetResult;
            }
        }

        return $fieldSetResult;
    }
}
