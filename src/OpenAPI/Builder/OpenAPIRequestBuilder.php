<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder;

use Membrane\Builder\Builder;
use Membrane\Builder\Specification;
use Membrane\OpenAPI\Builder\Internal\APIBuilder;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Filter;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Utility\Passes;

class OpenAPIRequestBuilder implements Builder
{
    private APIBuilder $apiBuilder;

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

        return $this->getApiBuilder()->fromSchema(
            $specification->requestBodySchema,
            'requestBody',
        );
    }

    /** @return Processor[] */
    private function fromParameters(OpenAPIRequest $specification): array
    {
        $queryParameters = array_filter(
            $specification->parameters,
            fn($p) => $p->in === In::Query,
        );

        $location = fn(array $chain) => [
            'required' => [],
            'fields' => [],
            'beforeSet' => $chain,
        ];
        $locations = [
            'path' => $location([new Filter\PathMatcher($specification->pathParameterExtractor)]),
            'query' => $location([new Filter\QueryStringToArray(array_combine(
                array_map(fn($p) => $p->name, $queryParameters),
                array_map(
                    fn($p) => [
                        'style' => $p->style->value,
                        'explode' => $p->explode,
                    ],
                    $queryParameters,
                )
            ))]),
            'header' => $location([]),
            'cookie' => $location([]),
        ];

        foreach ($specification->parameters as $parameter) {
            if ($parameter->hasMediaType() && $parameter->getMediaType() !== 'application/json') {
                assert($parameter->getMediaType() !== null);
                throw CannotProcessOpenAPI::unsupportedMediaTypes($parameter->getMediaType());
            }

            $locations[$parameter->in->value]['fields'][] = $this
                ->getApiBuilder()
                ->fromSchema(
                    $parameter->getSchema(),
                    $parameter->name,
                    true,
                    $parameter->in === In::Header,
                    $parameter->style->value,
                    $parameter->explode,
                );

            if ($parameter->required) {
                $locations[$parameter->in->value]['required'][] = $parameter->name;
            }
        }

        $fieldSets = [];
        foreach ($locations as $in => $location) {
            $fields = $location['fields'];
            $required = $location['required'];
            $beforeSet = $location['beforeSet'];

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

    private function getApiBuilder(): APIBuilder
    {
        return $this->apiBuilder ??= new APIBuilder();
    }
}
