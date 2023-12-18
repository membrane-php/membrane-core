<?php

declare(strict_types=1);

namespace Membrane;

use Membrane\Attribute\Builder as AttributeBuilder;
use Membrane\Builder\Builder;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Builder\RequestBuilder;
use Membrane\OpenAPI\Builder\ResponseBuilder;
use Membrane\Result\FieldName;
use Membrane\Result\Result;
use RuntimeException;

final class Membrane
{
    private bool $allowDefaults = true;

    /** @var Builder[] */
    private readonly array $builders;
    /** @var Builder[] */
    private readonly array $defaultBuilders;

    public function __construct(Builder ...$builders)
    {
        $this->builders = $builders;

        $this->defaultBuilders = [
            new AttributeBuilder(),
            new RequestBuilder(),
            new ResponseBuilder(),
        ];
    }

    public static function withoutDefaults(Builder ...$builders): self
    {
        $membrane = new Membrane(...$builders);
        $membrane->allowDefaults = false;
        return $membrane;
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
        $allowedBuilders = $this->builders;
        if ($this->allowDefaults) {
            array_push($allowedBuilders, ...$this->defaultBuilders);
        }

        foreach ($allowedBuilders as $builder) {
            if ($builder->supports($specification)) {
                return $builder->build($specification);
            }
        }

        //@TODO throw a proper exception here
        throw new RuntimeException('Unable to create processor for specification');
    }
}
