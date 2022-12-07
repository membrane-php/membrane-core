<?php

declare(strict_types=1);

namespace Membrane;

use Membrane\Attribute\Builder as AttributeBuilder;
use Membrane\Builder\Builder as BuilderInterface;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Builder\RequestBuilder;
use Membrane\OpenAPI\Builder\ResponseBuilder;
use Membrane\Result\FieldName;
use Membrane\Result\Result;
use RuntimeException;

final class Membrane
{
    /** @var BuilderInterface[] */
    private array $builders = [];

    public function __construct()
    {
        $this->builders[] = new AttributeBuilder();
        $this->builders[] = new RequestBuilder();
        $this->builders[] = new ResponseBuilder();
    }

    public function process(mixed $data, Specification ...$against): Result
    {
        $result = Result::noResult($data);

        foreach ($against as $specification) {
            $processor = $this->getProcessorFor($specification);
            $result = $processor->process(new FieldName(''), $data);

            if (!$result->isValid()) {
                return $result;
            }

            $data = $result->value;
        }

        return $result;
    }

    private function getProcessorFor(Specification $specification): Processor
    {
        foreach ($this->builders as $builder) {
            if ($builder->supports($specification)) {
                return $builder->build($specification);
            }
        }

        //@TODO throw a proper exception here
        throw new RuntimeException('Unable to create processor for specification');
    }
}
