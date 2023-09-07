<?php

declare(strict_types=1);

namespace Membrane\Console\Service;

use cebe\openapi\spec as Cebe;
use Membrane\Console\Template;
use Membrane\Filter\String\AlphaNumeric;
use Membrane\Filter\String\ToPascalCase;
use Membrane\OpenAPI\Builder\{OpenAPIRequestBuilder, OpenAPIResponseBuilder};
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Specification\{OpenAPIRequest, OpenAPIResponse};
use Membrane\OpenAPIReader\Exception\CannotRead;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\Reader;
use Membrane\Processor;
use Psr\Log\LoggerInterface;

class CacheOpenAPIProcessors
{
    private OpenAPIRequestBuilder $requestBuilder;
    private OpenAPIResponseBuilder $responseBuilder;
    private Template\Processor $processorTemplate;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Template\RequestBuilder $requestBuilderTemplate = new Template\RequestBuilder(),
        private readonly Template\ResponseBuilder $responseBuilderTemplate = new Template\ResponseBuilder()
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
            $openAPI = (new Reader([OpenAPIVersion::Version_3_0]))
                ->readFromAbsoluteFilePath($openAPIFilePath);
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

        if ($buildRequests) {
            // Create Request Directory if it doesn't exist.
            if (!file_exists("$destination/Request")) {
                mkdir("$destination/Request", recursive: true);
            }
        }

        // Initialize classMap for CachedBuilers
        $classMap = $classNames = [];

        foreach ($processors as $operationId => $operation) {
            $classNames[$operationId] = $this->createSuitableClassName($operationId, $classNames);
            $className = $classNames[$operationId];

            if (isset($operation['request'])) {
                $classMap[$operationId]['request'] = sprintf('%s\Request\%s', $cacheNamespace, $className);

                $this->logger->info("Caching $operationId Request at $destination/Request/$className.php");
                $this->cacheProcessor(
                    filePath: "$destination/Request/$className.php",
                    namespace: "$cacheNamespace\\Request",
                    className: $className,
                    processor: $operation['request']
                );
            }

            if (isset($operation['response'])) {
                $classMap[$operationId]['response'] = [];
                foreach ($operation['response'] as $code => $response) {
                    $prefixedCode = 'Code' . ucfirst((string)$code);
                    if (!file_exists("$destination/Response/$prefixedCode")) {
                        mkdir("$destination/Response/$prefixedCode", recursive: true);
                    }

                    $classMap[$operationId]['response'][(string)$code] =
                        sprintf('%s\Response\%s\%s', $cacheNamespace, $prefixedCode, $className);

                    $this->logger->info(
                        "Caching $operationId $code Response at $destination/Response/$prefixedCode/$className.php"
                    );
                    $this->cacheProcessor(
                        filePath: "$destination/Response/$prefixedCode/$className.php",
                        namespace: "$cacheNamespace\\Response\\$prefixedCode",
                        className: $className,
                        processor: $response
                    );
                }
            }
        }

        $this->logger->info('Processors cached successfully');

        if ($buildRequests) {
            $this->logger->info('Building CachedRequestBuilder');

            $cachedRequestBuilder = $this->requestBuilderTemplate->createFromTemplate(
                $cacheNamespace,
                $openAPIFilePath,
                array_map(fn($p) => $p['request'], $classMap)
            );

            file_put_contents(sprintf('%s/CachedRequestBuilder.php', $cacheDestinationFilePath), $cachedRequestBuilder);
        }

        if ($buildResponses) {
            $this->logger->info('Building CachedResponseBuilder');

            $cachedResponseBuilder = $this->responseBuilderTemplate->createFromTemplate(
                $cacheNamespace,
                $openAPIFilePath,
                array_filter(array_map(fn($p) => $p['response'] ?? null, $classMap))
            );

            file_put_contents(
                sprintf('%s/CachedResponseBuilder.php', $cacheDestinationFilePath),
                $cachedResponseBuilder
            );
        }

        return true;
    }

    private function cacheProcessor(string $filePath, string $namespace, string $className, Processor $processor): void
    {
        $contents = $this->getProcessorTemplate()->createFromTemplate($namespace, $className, $processor);

        file_put_contents($filePath, $contents);
    }

    private function getProcessorTemplate(): Template\Processor
    {
        if (!isset($this->processorTemplate)) {
            $this->processorTemplate = new Template\Processor();
            return $this->processorTemplate;
        }
        return $this->processorTemplate;
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
    private function buildProcessors(Cebe\OpenApi $openAPI, bool $buildRequests, bool $buildResponses): array
    {
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
                        new OpenAPIRequest(new PathParameterExtractor($pathUrl), $path, $methodObject)
                    );
                }

                if ($buildResponses) {
                    assert(!is_null($operation->responses));
                    $processors[$operation->operationId]['response'] = [];
                    foreach ($operation->responses->getResponses() as $code => $response) {
                        $this->logger->info("Building $code Response Processor");
                        if (!$response instanceof Cebe\Response) {
                            continue;
                        }

                        $processors[$operation->operationId]['response'][$code] = $this->getResponseBuilder()->build(
                            new OpenAPIResponse($operation->operationId, (string)$code, $response)
                        );
                    }
                }
            }
        }
        return $processors;
    }
}
