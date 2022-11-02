<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Processor;

use Exception;
use Membrane\Processor;
use Membrane\Result\FieldName;
use Membrane\Result\Result;

use function count;

class AllOf implements Processor
{
    /** @var Processor[] */
    public array $processors;

    public function __construct(private readonly string $processes, Processor ...$processors)
    {
        if (count($processors) < 2) {
            throw new Exception('AllOf Processor expects at least 2 processors');
        }
        $this->processors = $processors;
    }

    public function processes(): string
    {
        return $this->processes;
    }

    public function process(FieldName $parentFieldName, mixed $value): Result
    {
        $result = Result::noResult($value);

        foreach ($this->processors as $processor) {
            $itemResult = $processor->process($parentFieldName, $value);
            $result = $result->merge($itemResult);
        }

        return $result;
    }
}
