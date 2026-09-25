<?php

declare(strict_types=1);

namespace Membrane\Console\Service;

use Membrane\Console\Template;
use Membrane\Filter;
use Membrane\OpenAPI\Builder\Internal;
use Membrane\OpenAPI\ExtractPathParameters\PathParameterExtractor;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
use Membrane\OpenAPIReader\ValueObject\Valid\{Enum\Method, V30, V31};
use Psr\Log\LoggerInterface;

final class YieldsProcessors
{
    private Internal\Request $requestBuilder;
    private Internal\Response $responseBuilder;

    /** @var array<string, array{
     *      request: string,
     *      response: array<string>,
     *  }>
     */
    private(set) array $classMap;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $apiFilePath,
        private readonly string $cacheNamespace,
        private readonly bool $buildRequests,
        private readonly bool $buildResponses,
    ) {
    }

    public function __invoke(): \Generator
    {
        $this->classMap = [];

        $openAPI = $this->readOpenAPIFile($this->apiFilePath);

        $classNames = []; // to avoid duplicates classNames
        foreach ($openAPI->paths as $pathUrl => $path) {
            foreach ($path->getOperations() as $method => $operation) {
                $methodObject = Method::from(strtolower($method));
                $operationId = $operation->operationId;

                $classNames[$operationId] = $className =
                    $this->createSuitableClassName($operationId, $classNames);
                $operationMap = [
                    'request' => $this->getRequestFQCN($className),
                    'response' => [],
                ];

                if ($this->buildRequests) {
                    $this->logger
                        ->info("Generating {$this->getRequestFQCN($className)}");

                    yield new Template\Processor(
                        namespace: $this->getRequestNamespace(),
                        name: $className,
                        processor: $this->getRequestBuilder()->build(
                            new PathParameterExtractor($pathUrl),
                            $path,
                            $methodObject,
                        ),
                    );
                }

                if ($this->buildResponses) {
                    foreach ($operation->responses as $code => $response) {
                        $prefixedCode = 'Code' . ucwords((string) $code);

                        $operationMap['response'][] = $this
                            ->getResponseFQCN($prefixedCode, $className);

                        $this->logger
                            ->info("Generating {$this->getResponseFQCN($prefixedCode, $className)}");

                        yield new Template\Processor(
                            namespace: $this->getResponseNamespace($prefixedCode),
                            name: $className,
                            processor: $this->getResponseBuilder()->build($response),
                        );
                    }
                }

                $this->classMap[$operationId] = $operationMap;
            }
        }
    }

    private function getRequestNamespace(): string
    {
        return "{$this->cacheNamespace}\\Request";
    }

    private function getRequestFQCN(string $name): string
    {
        return "{$this->getRequestNamespace()}\\{$name}";
    }

    private function getResponseNamespace(string $code): string
    {
        return "{$this->cacheNamespace}\\Response\\{$code}";
    }

    private function getResponseFQCN(string $code, string $name): string
    {
        return "{$this->getResponseNamespace($code)}\\{$name}";
    }

    private function readOpenAPIFile(string $filepath): V30\OpenAPI | V31\OpenAPI
    {
        $this->logger->info("Reading OpenAPI from $filepath");
        return new MembraneReader([
            OpenAPIVersion::Version_3_0,
            OpenAPIVersion::Version_3_1
        ])->readFromAbsoluteFilePath($filepath);
    }

    /** @param array<string,string> $existingClassNames */
    private function createSuitableClassName(string $nameToConvert, array $existingClassNames): string
    {
        $pascalCaseName = new Filter\String\ToPascalCase()->filter($nameToConvert)->value;
        $alphanumericName = new Filter\String\AlphaNumeric()->filter($pascalCaseName)->value;
        assert(is_string($alphanumericName));

        if (is_numeric($alphanumericName[0])) {
            $alphanumericName = 'm' . $alphanumericName;
        }

        if (in_array($alphanumericName, $existingClassNames, true)) {
            $i = 1;
            do {
                $postfixedName = sprintf('%s%d', $alphanumericName, $i++);
            } while (in_array($postfixedName, $existingClassNames, true));

            return $postfixedName;
        }

        return $alphanumericName;
    }


    private function getRequestBuilder(): Internal\Request
    {
        return $this->requestBuilder ??= new Internal\Request();
    }

    private function getResponseBuilder(): Internal\Response
    {
        return $this->responseBuilder ??= new Internal\Response();
    }
}
