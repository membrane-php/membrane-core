<?php

declare(strict_types=1);

namespace Membrane\Console\Service;

use Atto\CodegenTools\ClassDefinition\PHPClassDefinitionProducer;
use Atto\CodegenTools\CodeGeneration\PHPFilesWriter;
use Membrane\Console\Template;
use Membrane\OpenAPIReader\Exception\CannotRead;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Psr\Log\LoggerInterface;

class CacheOpenAPIProcessors
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function cache(
        string $openAPIFilePath,
        string $cacheDestinationFilePath,
        string $cacheNamespace,
        bool $buildRequests = true,
        bool $buildResponses = true,
        bool $buildRouteMatch = false,
        string $requestClassname = 'CachedRequestBuilder',
        string $responseClassname = 'CachedResponseBuilder',
    ): bool {
        $yieldsClasses = new YieldsProcessors(
            $this->logger,
            $openAPIFilePath,
            $cacheNamespace,
            $buildRequests,
            $buildResponses,
        );

        $gensClasses = function () use (
            $yieldsClasses,
            $openAPIFilePath,
            $cacheNamespace,
            $buildRequests,
            $buildResponses,
            $buildRouteMatch,
            $requestClassname,
            $responseClassname,
        ) {
            yield from $yieldsClasses();

            if ($buildRequests) {
                if ($buildRouteMatch) {
                    yield new Template\RouteMatchBuilder(
                        $cacheNamespace,
                        $requestClassname,
                        $openAPIFilePath,
                        array_map(fn($p) => $p['request'], $yieldsClasses->classMap),
                    );
                } else {
                    yield new Template\RequestBuilder(
                        $cacheNamespace,
                        $requestClassname,
                        $openAPIFilePath,
                        array_map(fn($p) => $p['request'], $yieldsClasses->classMap),
                    );
                }
            }

            if ($buildResponses) {
                yield new Template\ResponseBuilder(
                    $cacheNamespace,
                    $responseClassname,
                    $openAPIFilePath,
                    array_map(fn($p) => $p['response'], $yieldsClasses->classMap),
                );
            }
        };

        try {
            $definitionProducer = new PHPClassDefinitionProducer($gensClasses());

            $destination = rtrim($cacheDestinationFilePath, '/');
            $classWriter = new PHPFilesWriter($destination, $cacheNamespace);
            $classWriter->writeFiles($definitionProducer);
        } catch (CannotRead | CannotSupport | InvalidOpenAPI | \RuntimeException $e) {
            // TODO do not catch RuntimeException once PHPFilesWriter throws specific exceptions
            $this->logger->error($e->getMessage());
            return false;
        }

        return true;
    }
}
