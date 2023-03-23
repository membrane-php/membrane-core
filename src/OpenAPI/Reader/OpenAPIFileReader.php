<?php

declare(strict_types=1);

namespace Membrane\OpenAPI\Reader;

use cebe\openapi\exceptions\TypeErrorException;
use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotReadOpenAPI;
use Symfony\Component\Yaml\Exception\ParseException;

class OpenAPIFileReader
{
    /** @var \Closure[] */
    private readonly array $supportedFileTypes;

    public function __construct()
    {
        $this->supportedFileTypes = [
            'json' => fn($p) => Reader::readFromJsonFile($p),
            'yaml' => fn($p) => Reader::readFromYamlFile($p),
            'yml' => fn($p) => Reader::readFromYamlFile($p),
        ];
    }

    public function readFromAbsoluteFilePath(string $absoluteFilePath): OpenApi
    {
        file_exists($absoluteFilePath) ?: throw CannotReadOpenAPI::fileNotFound($absoluteFilePath);

        $fileType = strtolower(pathinfo($absoluteFilePath, PATHINFO_EXTENSION));

        $readFrom = $this->supportedFileTypes[$fileType] ?? throw CannotReadOpenAPI::invalidFormat($fileType);

        try {
            $openApi = $readFrom($absoluteFilePath);
        } catch (\TypeError | TypeErrorException | ParseException $e) {
            throw CannotReadOpenAPI::notRecognizedAsOpenAPI(pathinfo($absoluteFilePath, PATHINFO_BASENAME), $e);
        } catch (UnresolvableReferenceException $e) {
            throw CannotProcessOpenAPI::unresolvedReference(pathinfo($absoluteFilePath, PATHINFO_BASENAME), $e);
        }

        assert($openApi instanceof OpenApi);
        $openApi->validate() ?: throw CannotProcessOpenAPI::invalidOpenAPI($absoluteFilePath, ...$openApi->getErrors());

        return $openApi;
    }
}
