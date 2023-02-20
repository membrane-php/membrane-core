<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Membrane\OpenAPI\Exception\CannotProcessOpenAPI;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\PathMatcher;
use Membrane\OpenAPI\Reader\OpenAPIFileReader;
use Membrane\OpenAPI\Specification\APISpec;
use Membrane\OpenAPI\Specification\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
#[CoversClass(APISpec::class)]
#[CoversClass(CannotProcessOpenAPI::class)]
#[CoversClass(CannotProcessRequest::class)]
#[UsesClass(PathMatcher::class)]
#[UsesClass(OpenAPIFileReader::class)]
class RequestTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    public static function dataSetsWithIncorrectMethods(): array
    {
        return [
            'no methods in path' => [
                'simple.json',
                '/path',
                Method::GET,
            ],
            'delete not in parampath' => [
                'noReferences.json',
                'http://test.com/parampath/01',
                Method::DELETE,
            ],
        ];
    }

    #[DataProvider('dataSetsWithIncorrectMethods')]
    #[Test]
    public function getOperationThrowsExceptionForIncorrectMethod(string $filePath, string $url, Method $method): void
    {
        self::expectExceptionObject(CannotProcessRequest::methodNotFound($method->value));

        new Request(self::DIR . $filePath, $url, $method);
    }

    #[Test]
    public function throwsExceptionIfRequestBodyFoundButContentNotJson(): void
    {
        self::expectExceptionObject(CannotProcessRequest::unsupportedContent());

        new Request(self::DIR . 'noReferences.json', 'http://test.com/path', Method::PUT);
    }


    public static function dataSetsWithValidSchemas(): array
    {
        return [
            [
                'http://test.com/path',
                Method::DELETE,
                'noReferences.json',
            ],
        ];
    }

    #[DataProvider('dataSetsWithValidSchemas')]
    #[Test]
    public function schemaIsSchemaObjectIfRequestBodyWithContentJson(string $url, Method $method, $filePath): void
    {
        $class = new Request(self::DIR . $filePath, $url, $method);

        self::assertInstanceOf(Schema::class, $class->requestBodySchema);
    }


    public static function dataSetsWithNullSchemas(): array
    {
        return [
            'requestBody not found' => [
                'http://test.com/path',
                Method::GET,
                'noReferences.json',
            ],
            'requestBody found with empty content array' => [
                'http://test.com/path',
                Method::POST,
                'noReferences.json',
            ],
        ];
    }

    #[DataProvider('dataSetsWithNullSchemas')]
    #[Test]
    public function schemaIsNullIfNoRequestBodyNorContent(string $url, Method $method, $filePath): void
    {
        $class = new Request(self::DIR . $filePath, $url, $method);

        self::assertNull($class->requestBodySchema);
    }

    #[Test]
    public function mergesPathAndOperationParameters(): void
    {
        $class = new Request(self::DIR . 'noReferences.json', 'http://test.com/parampath/01', Method::GET);

        self::assertContainsOnlyInstancesOf(Parameter::class, $class->pathParameters);

        $names = array_map(fn(Parameter $p) => $p->name, $class->pathParameters);
        self::assertContains('id', $names);
        self::assertContains('name', $names);
    }

    public static function dataSetsWithReferences(): array
    {
        return [
            'json file with references that must be resolved' => [
                'http://test.com/path',
                Method::GET,
                'references.json',
            ],
            'yaml file with references that must be resolved' => [
                'http://test.com/path',
                Method::GET,
                'references.yaml',
            ],
        ];
    }

    #[DataProvider('dataSetsWithReferences')]
    #[Test]
    public function ParameterSchemaReferencesResolved(string $url, Method $method, string $filePath): void
    {
        $class = new Request(self::DIR . $filePath, $url, $method);

        self::assertContainsOnlyInstancesOf(Parameter::class, $class->pathParameters);

        self::assertInstanceOf(Schema::class, $class->pathParameters[0]->schema);
    }

    #[Test]
    public function fromPsr7ThrowsExceptionIfUnsupportedMethod(): void
    {
        self::expectExceptionObject(new Exception('not supported'));

        $serverRequest = new ServerRequest('UPDATE', 'http://test.com/path');

        Request::fromPsr7(self::DIR . 'noReferences.json', $serverRequest);
    }

    #[Test]
    public function fromPsr7SuccessfulConstructionTest(): void
    {
        $expected = new Request(self::DIR . 'noReferences.json', 'http://test.com/path', Method::GET);
        $serverRequest = new ServerRequest('GET', 'http://test.com/path');

        $actual = Request::fromPsr7(self::DIR . 'noReferences.json', $serverRequest);

        self::assertEquals($expected, $actual);
    }
}
