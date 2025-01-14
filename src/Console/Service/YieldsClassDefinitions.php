<?php

declare(strict_types=1);

namespace Membrane\Console\Service;

use Membrane\Filter;
use Membrane\Console\Template;
use Membrane\OpenAPI\Builder\OpenAPIRequestBuilder;
use Membrane\OpenAPI\Builder\OpenAPIResponseBuilder;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Specification\OpenAPIRequest;
use Membrane\OpenAPI\Specification\OpenAPIResponse;
use Membrane\OpenAPIReader\Exception\CannotRead;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Method, V30, V31};

final class YieldsClassDefinitions
{
    private OpenAPIRequestBuilder $requestBuilder;
    private OpenAPIResponseBuilder $responseBuilder;

    public function __construct(
        private readonly \Psr\Log\LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        string $openAPIFilePath,
        string $cacheNamespace,
        bool $buildRequests,
        bool $buildResponses,
    ): \Generator {
        $openAPI = $this->readOpenAPIFile($openAPIFilePath);

        // Initialize classMap for CachedBuilers
        $classNames = [];
        $classMap = [];

        foreach ($openAPI->paths as $pathUrl => $path) {
            foreach ($path->getOperations() as $method => $operation) {
                $methodObject = Method::from(strtolower($method));
                $operationId = $operation->operationId;

                $classNames[$operationId] = $className =
                    $this->createSuitableClassName($operationId, $classNames);
                $classMap[$operationId] = [
                    'request' => "$cacheNamespace\\Request\\$className",
                    'response' => [],
                ];

                if ($buildRequests) {
                    $this->logger->info(
                        "Generating $cacheNamespace\\Request\\$className"
                    );

                    yield new Template\Processor(
                        namespace: "$cacheNamespace\\Request",
                        name: $className,
                        processor: $this->getRequestBuilder()->build(
                            new OpenAPIRequest(
                                new PathParameterExtractor($pathUrl),
                                $path,
                                $methodObject,
                            )
                        )
                    );
                }

                if ($buildResponses) {
                    foreach ($operation->responses as $code => $response) {
                        $prefixedCode = 'Code' . ucwords((string) $code);
                        $this->logger->info(
                            "Generating $cacheNamespace\\Response\\$prefixedCode\\$className"
                        );

                        yield new Template\Processor(
                            namespace: "$cacheNamespace\\Response\\$prefixedCode",
                            name: $className,
                            processor: $this->getResponseBuilder()->build(
                                new OpenAPIResponse(
                                    $operation->operationId,
                                    (string)$code,
                                    $response,
                                )
                            )
                        );
                    }
                }
            }

            if ($buildRequests) {
                yield new Template\RequestBuilder(
                    "$cacheNamespace",
                    $openAPIFilePath,
                    array_map(fn($p) => $p['request'], $classMap),
                );
            }

            if ($buildResponses) {
                yield new Template\ResponseBuilder(
                    "$cacheNamespace",
                    $openAPIFilePath,
                    array_map(fn($p) => $p['response'], $classMap),
                );
            }
        }
    }

    private function readOpenAPIFile(string $filepath): V30\OpenAPI | V31\OpenAPI
    {
        $this->logger->info("Reading OpenAPI from $filepath");
        return (new MembraneReader([
            OpenAPIVersion::Version_3_0,
            OpenAPIVersion::Version_3_1
        ]))->readFromAbsoluteFilePath($filepath);
    }

    /** @param array<string,string> $existingClassNames */
    private function createSuitableClassName(string $nameToConvert, array $existingClassNames): string
    {
        $pascalCaseName = (new Filter\String\ToPascalCase())->filter($nameToConvert)->value;
        $alphanumericName = (new Filter\String\AlphaNumeric())->filter($pascalCaseName)->value;

        assert(is_string($alphanumericName));
        if (is_numeric($alphanumericName[0])) {
            $alphanumericName = 'm' . $alphanumericName;
        }

        if (in_array($alphanumericName, $existingClassNames)) {
            $i = 1;
            do {
                $postfixedName = sprintf('%s%d', $alphanumericName, $i++);
            } while (in_array($postfixedName, $existingClassNames));

            return $postfixedName;
        }

        return $alphanumericName;
    }


    private function getRequestBuilder(): OpenAPIRequestBuilder
    {
        if (!isset($this->requestBuilder)) {
            $this->requestBuilder = new OpenAPIRequestBuilder();
            return $this->requestBuilder;
        }

        return $this->requestBuilder;
    }

    private function getResponseBuilder(): OpenAPIResponseBuilder
    {
        if (!isset($this->responseBuilder)) {
            $this->responseBuilder = new OpenAPIResponseBuilder();
            return $this->responseBuilder;
        }
        return $this->responseBuilder;
    }
}
