<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Builder;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Filter;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPI\Specification\Parameter;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Utility\Passes;

class OpenAPIRequestBuilder implements Builder
{
    private ParameterBuilder $parameterBuilder;

    public function supports(Specification $specification): bool
    {
        return $specification instanceof OpenAPIRequest;
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof OpenAPIRequest);

        $processors = $this->fromParameters($specification);
        $processors['body'] = $this->fromRequestBody($specification);

        return new RequestProcessor(
            '',
            $specification->operationId,
            $specification->method,
            $processors
        );
    }

    private function fromRequestBody(OpenAPIRequest $specification): Processor
    {
        if ($specification->requestBodySchema === null) {
            return new Field('requestBody', new Passes());
        }

        return $this->getParameterBuilder()->fromSchema(
            $specification->requestBodySchema,
            'requestBody',
        );
    }

    /** @return Processor[] */
    private function fromParameters(OpenAPIRequest $specification): array
    {
        $parameters = array_map(
            fn($p) => new Parameter($p),
            $specification->parameters,
        );

        $queryParameters = array_filter($parameters, fn($p) => $p->in === 'query');

        $location = fn(array $chain) => ['required' => [], 'fields' => [], 'beforeSet' => $chain];
        $locations = [
            'path' => $location([new Filter\PathMatcher($specification->pathParameterExtractor)]),
            'query' => $location([new Filter\QueryStringToArray(array_combine(
                array_map(fn($p) => $p->name, $queryParameters),
                array_map(
                    fn($p) => ['style' => $p->style, 'explode' => $p->explode],
                    $queryParameters,
                )
            ))]),
            'header' => $location([]),
            'cookie' => $location([]),
        ];

        foreach ($parameters as $parameter) {
            $locations[$parameter->in]['fields'][] = $this
                ->getParameterBuilder()
                ->fromParameter(
                    $parameter,
                    true,
                    $parameter->in === In::Header->value,
                );

            if ($parameter->required) {
                $locations[$parameter->in]['required'][] = $parameter->name;
            }
        }

        $fieldSets = [];
        foreach ($locations as $in => ['required' => $required, 'fields' => $fields, 'beforeSet' => $beforeSet]) {
            if (!empty($required)) {
                $beforeSet[] = new RequiredFields(...$required);
            }

            if (!empty($beforeSet)) {
                $fields[] = new BeforeSet(...$beforeSet);
            }

            $fieldSets[$in] = new FieldSet($in, ...$fields);
        }

        return $fieldSets;
    }

    private function getParameterBuilder(): ParameterBuilder
    {
        if (!isset($this->parameterBuilder)) {
            $this->parameterBuilder = new ParameterBuilder();
        }
        return $this->parameterBuilder;
    }
}
