<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Processor;
use Membrane\Result\Fieldname;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use RuntimeException;

class Fieldset implements Processor
{
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

    public function process(Fieldname $parentFieldname, mixed $value): Result
    {
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

        $fieldname = $parentFieldname->push(new Fieldname($this->processes));
        $fieldsetResult = Result::noResult($value);

        if (isset($this->before)) {
            $result = $this->before->process($fieldname, $value);
            $value = $result->value;
            $fieldsetResult = $fieldsetResult->merge($result);

            if (!$fieldsetResult->isValid()) {
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

        $fieldsetResult = $fieldsetResult->merge(Result::noResult($value));

        if (isset($this->after) && $fieldsetResult->isValid()) {
            $result = $this->after->process($fieldname, $value);
            $fieldsetResult = $fieldsetResult->merge($result);

            if (!$fieldsetResult->isValid()) {
                return $fieldsetResult;
            }
        }

        return $fieldsetResult;
    }
}
