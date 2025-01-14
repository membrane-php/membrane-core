<?php

declare(strict_types=1);

namespace Membrane\Console\Service;

use Atto\CodegenTools\ClassDefinition\PHPClassDefinitionProducer;
use Atto\CodegenTools\CodeGeneration\PHPFilesWriter;
use Membrane\OpenAPIReader\Exception\CannotRead;
use Membrane\OpenAPIReader\Exception\CannotSupport;
use Membrane\OpenAPIReader\Exception\InvalidOpenAPI;
use Membrane\OpenAPIReader\MembraneReader;
use Membrane\OpenAPIReader\OpenAPIVersion;
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
        bool $buildResponses = true
    ): bool {
        $yieldsClasses = new YieldsClassDefinitions($this->logger);

        try {
            $definitionProducer = new PHPClassDefinitionProducer($yieldsClasses(
                $openAPIFilePath,
                $cacheNamespace,
                $buildRequests,
                $buildResponses,
            ));

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
