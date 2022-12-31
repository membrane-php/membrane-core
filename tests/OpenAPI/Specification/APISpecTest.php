<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\exceptions\UnresolvableReferenceException;
use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Response;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\Exception\CannotReadOpenAPI;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Specification\APISpec;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * @covers \Membrane\OpenAPI\Specification\APISpec
 * @covers \Membrane\OpenAPI\Exception\CannotReadOpenAPI
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 * @covers \Membrane\OpenAPI\Exception\CannotProcessRequest
 * @uses   \Membrane\OpenAPI\PathMatcher
 */
class APISpecTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    /** @test */
    public function throwExceptionForNonExistentFilePath(): void
    {
        self::expectExceptionObject(CannotReadOpenAPI::fileNotFound('nowhere/nothing.json'));

        new class('nowhere/nothing.json', '/testpath') extends APISpec {
        };
    }

    /** @test */
    public function throwExceptionForRelativeFilePath(): void
    {
        $fileName = 'petstore.yaml';
        $relativeFilePath = './tests/fixtures/OpenAPI/docs/' . $fileName;
        $previous = new UnresolvableReferenceException();
        self::expectExceptionObject(CannotReadOpenAPI::unresolvedReference($fileName, $previous));

        new class($relativeFilePath, '/path') extends APISpec {
        };
    }

    /** @test */
    public function throwExceptionForInvalidFileType(): void
    {
        $filePath = __FILE__;
        self::expectExceptionObject(CannotReadOpenAPI::fileTypeNotSupported(pathinfo($filePath, PATHINFO_EXTENSION)));

        new class($filePath, '/testpath') extends APISpec {
        };
    }

    public function dataSetsNotFollowingOpenAPIFormat(): array
    {
        return [
            'empty json' => [
                'empty.json',
                new \TypeError(),
            ],
            'empty yml' => [
                'empty.yml',
                new \TypeError(),
            ],
            'invalid json' => [
                'invalid.json',
                new \TypeError(),
            ],
            'invalid yaml' => [
                'invalid.yaml',
                new ParseException(''),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsNotFollowingOpenAPIFormat
     */
    public function throwExceptionForNotFollowingOpenAPIFormat(string $fileName, \Throwable $e): void
    {
        $filePath = self::DIR . $fileName;
        self::expectExceptionObject(CannotReadOpenAPI::cannotParse($fileName, $e));

        new class($filePath, '/path') extends APISpec {
        };
    }

    public function dataSetsFollowingOpenAPIFormatIncorrectly(): array
    {
        return [
            'invalid OpenAPI json' => [
                'invalidAPI.json',
            ],
            'invalid OpenAPI yaml' => [
                'invalidAPI.yaml',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsFollowingOpenAPIFormatIncorrectly
     */
    public function throwsExceptionForInvalidOpenAPI(string $fileName): void
    {
        self::expectExceptionObject(CannotReadOpenAPI::invalidOpenAPI($fileName));

        new class(self::DIR . $fileName, '/path') extends APISpec {
        };
    }

    /** @test */
    public function throwsExceptionIfNoPathMatches(): void
    {
        $fileName = 'noReferences.json';
        $url = 'incorrect/path';
        self::expectExceptionObject(CannotProcessRequest::pathNotFound($fileName, $url));

        new class(self::DIR . $fileName, $url) extends APISpec {
        };
    }

    public function dataSetsThatPass(): array
    {
        return [
            'GET does not have any content' => [
                'noReferences.json',
                'http://test.com/path',
                Method::GET,
            ],
            'POST has empty content' => [
                'noReferences.json',
                'http://test.com/path',
                Method::POST,
            ],
            'DELETE has application/json content' => [
                'noReferences.json',
                'http://test.com/path',
                Method::DELETE,
            ],
            'path that contains reference that must be resolved .json' => [
                'references.json',
                'http://test.com/path',
                Method::GET,
            ],
            'path that contains reference that must be resolved .yaml' => [
                'references.json',
                'http://test.com/path',
                Method::GET,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsThatPass
     */
    public function successfulConstructionForValidInputs(string $filePath, string $url, Method $method): void
    {
        $class = new class(self::DIR . $filePath, $url, $method) extends APISpec {
            public Operation $requestOperation;

            public function __construct(string $filePath, string $url, Method $method)
            {
                parent::__construct($filePath, $url);
                $this->requestOperation = $this->getOperation($method);
            }
        };

        self::assertInstanceOf(PathItem::class, $class->pathItem);
        self::assertInstanceOf(Operation::class, $class->requestOperation);
        self::assertInstanceOf(Response::class, $class->pathItem->get->responses->getResponse('200'));
    }
}
