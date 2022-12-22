<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Schema;
use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Specification\Request;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\Request
 * @covers \Membrane\OpenAPI\Specification\APISpec
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 * @covers \Membrane\OpenAPI\Exception\CannotProcessRequest
 * @uses   \Membrane\OpenAPI\PathMatcher
 */
class RequestTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    public function dataSetsWithIncorrectMethods(): array
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

    /**
     * @test
     * @dataProvider dataSetsWithIncorrectMethods
     */
    public function getOperationThrowsExceptionForIncorrectMethod(string $filePath, string $url, Method $method): void
    {
        self::expectExceptionObject(CannotProcessRequest::methodNotFound($method->value));

        new Request(self::DIR . $filePath, $url, $method);
    }

    /**
     * @test
     */
    public function throwsExceptionIfRequestBodyFoundButContentNotJson(): void
    {
        self::expectExceptionObject(CannotProcessRequest::unsupportedContent());

        new Request(self::DIR . 'noReferences.json', 'http://test.com/path', Method::PUT);
    }


    public function dataSetsWithValidSchemas(): array
    {
        return [
            [
                'http://test.com/path',
                Method::DELETE,
                'noReferences.json',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsWithValidSchemas
     */
    public function schemaIsSchemaObjectIfRequestBodyWithContentJson(string $url, Method $method, $filePath): void
    {
        $class = new Request(self::DIR . $filePath, $url, $method);

        self::assertInstanceOf(Schema::class, $class->requestBodySchema);
    }


    public function dataSetsWithNullSchemas(): array
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

    /**
     * @test
     * @dataProvider dataSetsWithNullSchemas
     */
    public function schemaIsNullIfNoRequestBodyNorContent(string $url, Method $method, $filePath): void
    {
        $class = new Request(self::DIR . $filePath, $url, $method);

        self::assertNull($class->requestBodySchema);
    }

    /**
     * @test
     */
    public function mergesPathAndOperationParameters(): void
    {
        $class = new Request(self::DIR . 'noReferences.json', 'http://test.com/parampath/01', Method::GET);

        self::assertContainsOnlyInstancesOf(Parameter::class, $class->pathParameters);

        $names = array_map(fn(Parameter $p) => $p->name, $class->pathParameters);
        self::assertContains('id', $names);
        self::assertContains('name', $names);
    }

    public function dataSetsWithReferences(): array
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

    /**
     * @test
     * @dataProvider dataSetsWithReferences
     */
    public function ParameterSchemaReferencesResolved(string $url, Method $method, string $filePath): void
    {
        $class = new Request(self::DIR . $filePath, $url, $method);

        self::assertContainsOnlyInstancesOf(Parameter::class, $class->pathParameters);

        self::assertInstanceOf(Schema::class, $class->pathParameters[0]->schema);
    }

    /** @test */
    public function fromPsr7ThrowsExceptionIfUnsupportedMethod(): void
    {
        self::expectExceptionObject(new Exception('not supported'));

        $serverRequest = new ServerRequest('UPDATE', 'http://test.com/path');

        Request::fromPsr7(self::DIR . 'noReferences.json', $serverRequest);
    }

    /** @test */
    public function fromPsr7SuccessfulConstructionTest(): void
    {
        $expected = new Request(self::DIR . 'noReferences.json', 'http://test.com/path', Method::GET);
        $serverRequest = new ServerRequest('GET', 'http://test.com/path');

        $actual = Request::fromPsr7(self::DIR . 'noReferences.json', $serverRequest);

        self::assertEquals($expected, $actual);
    }
}
