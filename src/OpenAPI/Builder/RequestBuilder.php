<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use Exception;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Filter\HTTPParameters;
use Membrane\OpenAPI\Filter\PathMatcher as PathMatcherFilter;
use Membrane\OpenAPI\Processor\Json;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\Request;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Utility\Passes;

class RequestBuilder extends APIBuilder
{
    public function supports(Specification $specification): bool
    {
        return ($specification instanceof Request);
    }

    public function build(Specification $specification): Processor
    {
        assert($specification instanceof Request);

        $processors = $this->fromParameters($specification);
        $processors['body'] = new Json($this->fromRequestBody($specification));

        return new RequestProcessor('', $processors);
    }

    private function fromRequestBody(Request $specification): Processor
    {
        if ($specification->requestBodySchema === null) {
            return new Field('requestBody', new Passes());
        }

        return $this->fromSchema($specification->requestBodySchema, 'requestBody');
    }

    /**
     * @return Processor[]
     */
    private function fromParameters(Request $specification): array
    {
        $locationFields = [
            'path' => [
                'required' => [],
                'fields' => [],
                'beforeSet' => [new PathMatcherFilter($specification->matchingPath)],
            ],
            'query' => ['required' => [], 'fields' => [], 'beforeSet' => [new HTTPParameters()]],
            'header' => ['required' => [], 'fields' => [], 'beforeSet' => []],
            'cookie' => ['required' => [], 'fields' => [], 'beforeSet' => []],
        ];

        foreach ($specification->pathParameters as $p) {
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
        $schemaLocations = [];

        if ($parameter->schema !== null) {
            $schemaLocations[] = $parameter->schema;
        }

        if ($parameter->content !== []) {
            $schemaLocations[] = $parameter->content['application/json']?->schema
                ??
                throw new Exception('APISpec requires application/json content');
        }

        if (count($schemaLocations) !== 1) {
            throw new Exception(
                'A parameter MUST contain either a schema property, or a content property, but not both'
            );
        }

        assert($schemaLocations[0] instanceof Schema);
        return $schemaLocations[0];
    }
}
