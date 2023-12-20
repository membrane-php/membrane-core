<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Builder;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\OpenAPI\Filter\PathMatcher as PathMatcherFilter;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPI\Specification\Parameter;
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

        return $this->getParameterBuilder()->fromSchema($specification->requestBodySchema, 'requestBody');
    }

    /** @return Processor[] */
    private function fromParameters(OpenAPIRequest $specification): array
    {
        $locationFields = [
            'path' => [
                'required' => [],
                'fields' => [],
                'beforeSet' => [new PathMatcherFilter($specification->pathParameterExtractor)],
            ],
            'query' => ['required' => [], 'fields' => [], 'beforeSet' => [new HTTPParameters()]],
            'header' => ['required' => [], 'fields' => [], 'beforeSet' => []],
            'cookie' => ['required' => [], 'fields' => [], 'beforeSet' => []],
        ];

        foreach (array_map(fn($p) => new Parameter($p), $specification->parameters) as $parameter) {
            $locationFields[$parameter->in]['fields'][] = $this->getParameterBuilder()
                ->fromParameter($parameter, true);
            if ($parameter->required) {
                $locationFields[$parameter->in]['required'][] = $parameter->name;
            }
        }

        $fieldSets = [];
        foreach ($locationFields as $in => ['required' => $required, 'fields' => $fields, 'beforeSet' => $beforeSet]) {
            if (count($required) > 0) {
                $beforeSet[] = new RequiredFields(...$required);
            }

            if (count($beforeSet) > 0) {
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
