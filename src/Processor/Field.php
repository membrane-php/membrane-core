<?php

declare(strict_types=1);

namespace Membrane\Processor;

use Membrane\Filter;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator;

class Field implements Processor
{
    /** @var Filter[]|Validator[] */
    private array $chain;

    public function __construct(
        private readonly string $processes,
        Filter|Validator ...$chain
    ) {
        $this->chain = $chain;
    }

    public function __toString(): string
    {
        $conditions = [];
        foreach ($this->chain as $item) {
            $condition = (string)$item;
            if ($condition !== '') {
                $conditions[] = sprintf("\n\t- %s", $item);
            }
        }

        if ($conditions === []) {
            return '';
        } else {
            return ($this->processes === '' ? '' : sprintf('"%s":', $this->processes)) .
                implode('.', $conditions) .
                '.';
        }
    }

    public function __toPHP(): string
    {
        return sprintf(
            'new %s("%s"%s)',
            self::class,
            $this->processes(),
            implode('', array_map(fn($p) => ', ' . $p->__toPHP(), $this->chain))
        );
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $result = Result::noResult($value);

        foreach ($this->chain as $item) {
            if ($item instanceof Validator) {
                $result = $result->merge($item->validate($result->value));
            }

            if ($item instanceof Filter) {
                $result = $result->merge($item->filter($result->value));
            }

            if (!$result->isValid()) {
                $messageSet = new MessageSet($parentFieldName->push(new Fieldname($this->processes)));

                if ($result->messageSets !== []) {
                    $messageSet = $messageSet->merge(current($result->messageSets));
                }

                return new Result(
                    $result->value,
                    $result->result,
                    $messageSet
                );
            }
        }

        return $result;
    }
}
