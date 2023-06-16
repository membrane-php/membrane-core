<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\OpenAPI\Filter\PathMatcher as PathMatcherFilter;
use Membrane\OpenAPI\Processor\Json;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Utility\Passes;

class OpenAPIRequestBuilder extends APIBuilder
{
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

        return $this->fromSchema($specification->requestBodySchema, 'requestBody');
    }

    /**
     * @return Processor[]
     */
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

        foreach ($specification->parameters as $p) {
            $locationFields[$p->in]['fields'][] = $this->fromSchema($this->findSchema($p), $p->name, false);
            if ($p->required) {
                $locationFields[$p->in]['required'][] = $p->name;
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

    private function findSchema(Parameter $parameter): Schema
    {
        $schemaLocations = null;

        if ($parameter->schema !== null) {
            $schemaLocations = $parameter->schema;
        }

        if ($parameter->content !== []) {
            $schemaLocations = $parameter->content['application/json']?->schema
                ??
                throw CannotProcessOpenAPI::unsupportedMediaTypes(array_keys($parameter->content));
        }

        // Cebe library already validates that parameters MUST have either a schema or content but not both.
        assert($schemaLocations instanceof Schema);

        return $schemaLocations;
    }
}
