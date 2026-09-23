<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Builder\Internal;

use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessSpecification;
use Membrane\OpenAPI\ExtractPathParameters\ExtractsPathParameters;
use Membrane\OpenAPI\Filter;
use Membrane\OpenAPI\Processor\Request as RequestProcessor;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Method, V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\In;
use Membrane\Processor;
use Membrane\Processor\BeforeSet;
use Membrane\Processor\Field;
use Membrane\Processor\FieldSet;
use Membrane\Validator\FieldSet\RequiredFields;
use Membrane\Validator\Utility\Passes;

/**
 * @internal see README.md
 */
class Request
{
    private Schema $schemaBuilder {
        get => $this->schemaBuilder ??= new Schema();
    }

    public function build(
        ExtractsPathParameters $pathParameterExtractor,
        V30\PathItem | V31\PathItem $pathItem,
        Method $method,
    ): Processor {
        $operation = $pathItem->getOperations()[$method->value]
            ?? throw CannotProcessSpecification::methodNotFound($method->value);

        return new RequestProcessor(
            '',
            $operation->operationId,
            $method,
            [
                'body' => $this->fromRequestBody($operation->requestBody),
                ...$this->fromParameters(
                    $pathParameterExtractor,
                    $operation->parameters,
                ),
            ],
        );
    }

    private function fromRequestBody(
        null | V30\RequestBody | V31\RequestBody $requestBody,
    ): Processor {
        if ($requestBody === null || $requestBody->content === []) {
            return new Field('requestBody', new Passes());
        }

        foreach ($requestBody->content as $contentType => $mediaType) {
            if (
                ContentType::fromContentTypeHeader($contentType) !== ContentType::Unmatched
                && $mediaType->schema !== null
            ) {
                return $this->schemaBuilder->fromSchema(
                    $mediaType->schema,
                    'requestBody',
                );
            }
        }

        throw CannotProcessOpenAPI::unsupportedMediaTypes(
            ...array_keys($requestBody->content),
        );
    }

    /**
     * @param V30\Parameter[] | V31\Parameter[] $parameters
     *
     * @return Processor[]
     */
    private function fromParameters(
        ExtractsPathParameters $extractsPathParameters,
        array $parameters,
    ): array {
        $queryParameters = array_filter(
            $parameters,
            fn($p) => $p->in === In::Query,
        );

        $location = fn(array $chain) => [
            'required' => [],
            'fields' => [],
            'beforeSet' => $chain,
        ];
        $locations = [
            'path' => $location([new Filter\PathMatcher($extractsPathParameters)]),
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

        foreach ($parameters as $parameter) {
            if ($parameter->hasMediaType() && $parameter->getMediaType() !== 'application/json') {
                assert($parameter->getMediaType() !== null);
                throw CannotProcessOpenAPI::unsupportedMediaTypes($parameter->getMediaType());
            }

            $locations[$parameter->in->value]['fields'][] = $this
                ->schemaBuilder
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
}
