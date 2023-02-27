<?php

declare(strict_types=1);

namespace OpenAPI\Reader;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use Membrane\OpenAPI\Exception\CannotReadOpenAPI;
use Membrane\OpenAPI\Reader\OpenAPIFileReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;
use TypeError;

#[CoversClass(OpenAPIFileReader::class)]
#[CoversClass(CannotReadOpenAPI::class)]
class OpenAPIFileReaderTest extends TestCase
{
    public const FIXTURES = __DIR__ . '/../../fixtures/OpenAPI/';

    public static function provideInvalidFiles(): array
    {
        return [
            'Non-existent file throws CannotReadOpenAPI::fileNotFound' => [
                CannotReadOpenAPI::fileNotFound('nowhere/nothing.json'),
                'nowhere/nothing.json',
            ],
            'Relative file path throws CannotReadOpenAPI::unresolvedReference' => [
                CannotReadOpenAPI::unresolvedReference('petstore.yaml', new UnresolvableReferenceException()),
                './tests/fixtures/OpenAPI/docs/petstore.yaml',
            ],
            'Unsupported file type throws CannotReadOpenAPI::fileTypeNotSupported' => [
                CannotReadOpenAPI::fileTypeNotSupported('php'),
                __FILE__,
            ],
            'Empty .json file throws CannotReadOpenAPI::cannotParse' => [
                CannotReadOpenAPI::cannotParse('empty.json', new TypeError()),
                self::FIXTURES . 'empty.json',
            ],
            'Empty .yml file throws CannotReadOpenAPI::cannotParse' => [
                CannotReadOpenAPI::cannotParse('empty.yml', new TypeError()),
                self::FIXTURES . 'empty.yml',
            ],
            '.json file in invalid json format throws CannotReadOpenAPI::cannotParse' => [
                CannotReadOpenAPI::cannotParse('invalid.json', new TypeError()),
                self::FIXTURES . 'invalid.json',
            ],
            '.yaml file in invalid yaml format throws CannotReadOpenAPI::cannotParse' => [
                CannotReadOpenAPI::cannotParse('invalid.yaml', new ParseException('')),
                self::FIXTURES . 'invalid.yaml',
            ],
        ];
    }

    #[DataProvider('provideInvalidFiles')]
    #[Test]
    public function exceptionHandlingTest(CannotReadOpenAPI $expected, string $filePath): void
    {
        self::expectExceptionObject($expected);

        (new OpenAPIFileReader())->readFromAbsoluteFilePath($filePath);
    }

    public static function provideInvalidOpenAPI(): array
    {
        return [
            '.json file in invalid OpenAPI format throws CannotReadOpenAPI::invalidOpenAPI' => [
                self::FIXTURES . 'invalidAPI.json',
                (Reader::readFromJsonFile(self::FIXTURES . 'invalidAPI.json'))->getErrors(),
            ],
            '.yaml file in invalid OpenAPI format throws CannotReadOpenAPI::invalidOpenAPI' => [
                self::FIXTURES . 'invalidAPI.yaml',
                (Reader::readFromYamlFile(self::FIXTURES . 'invalidAPI.yaml'))->getErrors(),
            ],
        ];
    }

    #[Test]
    #[TestDox('Invalid Open API Specs will throw Exceptions which contain reasons it was considered invalid')]
    #[DataProvider('provideInvalidOpenAPI')]
    public function throwsExceptionForInvalidOpenAPISpecs(string $filePath, array $errors): void
    {
        self::expectExceptionObject(CannotReadOpenAPI::invalidOpenAPI(...$errors));

        (new OpenAPIFileReader())->readFromAbsoluteFilePath($filePath);
    }

    #[Test]
    public function readFromAbsoluteFilePathTest(): void
    {
        $expected = OpenApi::class;
        $sut = new OpenAPIFileReader();

        $actual = $sut->readFromAbsoluteFilePath(self::FIXTURES . 'simple.json');

        self::assertInstanceOf($expected, $actual);
    }
}
