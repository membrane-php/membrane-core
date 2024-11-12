<?php

declare(strict_types=1);

namespace Membrane\Console\Service;

use Atto\CodegenTools\ClassDefinition\PHPClassDefinitionProducer;
use Atto\CodegenTools\CodeGeneration\PHPFilesWriter;
use Membrane\Console\Template;
use Membrane\Filter\String\AlphaNumeric;
use Membrane\Filter\String\ToPascalCase;
use Membrane\OpenAPI\Builder\{OpenAPIRequestBuilder, OpenAPIResponseBuilder};
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Specification\{OpenAPIRequest, OpenAPIResponse};
use Membrane\OpenAPIReader\Exception\CannotRead;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{V30, V31};
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\Processor;
use Psr\Log\LoggerInterface;

class CacheOpenAPIProcessors
{
    private OpenAPIRequestBuilder $requestBuilder;
    private OpenAPIResponseBuilder $responseBuilder;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function cache(
        string $openAPIFilePath,
        string $cacheDestinationFilePath,
        string $cacheNamespace,
        bool $buildRequests = true,
        bool $buildResponses = true
    ): bool {
        $this->logger->info("Reading OpenAPI from $openAPIFilePath");
        try {
            $openAPI = (new MembraneReader([
                OpenAPIVersion::Version_3_0,
                //OpenAPIVersion::Version_3_1, //TODO support 3.1
            ]))->readFromAbsoluteFilePath($openAPIFilePath);
        } catch (CannotRead | CannotSupport | InvalidOpenAPI $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        $this->logger->info("Checking for write permission to $cacheDestinationFilePath");
        if (!$this->isDestinationAWriteableDirectory($cacheDestinationFilePath)) {
            return false;
        }

        $processors = $this->buildProcessors($openAPI, $buildRequests, $buildResponses);

        $destination = rtrim($cacheDestinationFilePath, '/');

        // Initialize classMap for CachedBuilers
        $classMap = $classNames = [];
        $classDefinitions = [];

        foreach ($processors as $operationId => $operation) {
            $classNames[$operationId] = $this->createSuitableClassName($operationId, $classNames);
            $className = $classNames[$operationId];

            if (isset($operation['request'])) {
                $classMap[$operationId]['request'] = sprintf('%s\Request\%s', $cacheNamespace, $className);

                $this->logger->info("Caching $operationId Request at $destination/Request/$className.php");
                $classDefinitions[] = new Template\Processor(
                    "$cacheNamespace\\Request",
                    $className,
                    $operation['request']
                );
            }

            if (isset($operation['response'])) {
                $classMap[$operationId]['response'] = [];
                foreach ($operation['response'] as $code => $response) {
                    $prefixedCode = 'Code' . ucfirst((string)$code);
                    $classMap[$operationId]['response'][(string)$code] =
                        sprintf('%s\Response\%s\%s', $cacheNamespace, $prefixedCode, $className);

                    $this->logger->info(
                        "Caching $operationId $code Response at $destination/Response/$prefixedCode/$className.php"
                    );

                    $classDefinitions[] = new Template\Processor(
                        "$cacheNamespace\\Response\\$prefixedCode",
                        $className,
                        $response
                    );
                }
            }
        }

        $this->logger->info('Processors cached successfully');

        if ($buildRequests) {
            $this->logger->info('Building CachedRequestBuilder');

            $classDefinitions[] = new Template\RequestBuilder(
                $cacheNamespace,
                $openAPIFilePath,
                array_map(fn($p) => $p['request'], $classMap)
            );
        }

        if ($buildResponses) {
            $this->logger->info('Building CachedResponseBuilder');

            $classDefinitions[] = new Template\ResponseBuilder(
                $cacheNamespace,
                $openAPIFilePath,
                array_filter(array_map(fn($p) => $p['response'] ?? null, $classMap))
            );
        }

        $definitionProducer = new PHPClassDefinitionProducer((function () use ($classDefinitions) {
            yield from $classDefinitions;
        })());

        try {
            $classWriter = new PHPFilesWriter($destination, $cacheNamespace);
            $classWriter->writeFiles($definitionProducer);
        } catch (\RuntimeException) {
            return false;
        }

        return true;
    }

    private function isDestinationAWriteableDirectory(string $destination): bool
    {
        while (!file_exists($destination)) {
            $destination = dirname($destination);
        }

        if (is_dir($destination) && is_writable($destination)) {
            return true;
        }

        $this->logger->error("Cannot write to $destination");
        return false;
    }

    /** @param array<string,string> $existingClassNames */
    private function createSuitableClassName(string $nameToConvert, array $existingClassNames): string
    {
        $pascalCaseName = (new ToPascalCase())->filter($nameToConvert)->value;
        $alphanumericName = (new AlphaNumeric())->filter($pascalCaseName)->value;

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

    /**
     * @return array<string, array{
     *              'request'?: Processor,
     *              'response'?: array<string,Processor>
     *          }>
     */
    private function buildProcessors(
        V30\OpenAPI | V31\OpenAPI $openAPI,
        bool $buildRequests,
        bool $buildResponses,
    ): array {
        $processors = [];
        foreach ($openAPI->paths as $pathUrl => $path) {
            $this->logger->info("Building Processors for $pathUrl");
            foreach ($path->getOperations() as $method => $operation) {
                $methodObject = Method::tryFrom(strtolower($method));
                if ($methodObject === null) {
                    $this->logger->warning("$method not supported and will be skipped.");
                    continue;
                }

                if ($buildRequests) {
                    $this->logger->info('Building Request processor');
                    $processors[$operation->operationId]['request'] = $this->getRequestBuilder()->build(
                        new OpenAPIRequest(
                            new PathParameterExtractor($pathUrl),
                            $path,
                            $methodObject,
                        )
                    );
                }

                if ($buildResponses) {
                    $processors[$operation->operationId]['response'] = [];
                    foreach ($operation->responses as $code => $response) {
                        $this->logger->info("Building $code Response Processor");

                        $processors[$operation->operationId]['response'][$code] = $this->getResponseBuilder()->build(
                            new OpenAPIResponse(
                                $operation->operationId,
                                (string)$code,
                                $response,
                            )
                        );
                    }
                }
            }
        }
        return $processors;
    }
}
