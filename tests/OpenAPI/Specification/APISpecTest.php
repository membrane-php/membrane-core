<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Response;
use Exception;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Specification\APISpec;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\APISpec
 * @uses   \Membrane\OpenAPI\PathMatcher
 */
class APISpecTest extends TestCase
{
    public const DIR = __DIR__ . '/../../fixtures/OpenAPI/';

    /** @test */
    public function throwExceptionForRelativeFilePaths(): void
    {
        self::expectExceptionObject(
            new Exception('absolute file path required to resolve references in OpenAPI specifications')
        );

        new class('./tests/fixtures/OpenAPI/docs/petstore.yaml', '/path') extends APISpec {
        };
    }

    /** @test */
    public function throwExceptionForNonExistentFilePaths(): void
    {
        self::expectExceptionObject(new Exception('File could not be found'));

        new class('nonExistentFilePath', '/testpath') extends APISpec {
        };
    }

    /** @test */
    public function throwExceptionForInvalidFileTypes(): void
    {
        self::expectExceptionObject(new Exception('Invalid file type'));

        new class(self::DIR . 'invalidFileType.txt', '/testpath') extends APISpec {
        };
    }

    public function dataSetsForInvalidFormats(): array
    {
        return [
            'json file that is not OpenAPI format' => [
                'notOpenAPI.json',
                'json file is not following OpenAPI specifications',
            ],
            'yml/yaml file that is not OpenAPI format' => [
                'notOpenAPI.yml',
                'yml file is not following OpenAPI specifications',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsForInvalidFormats
     */
    public function throwsExceptionForInvalidFormats(string $filePath, string $exceptionMessage): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage($exceptionMessage);

        new class(self::DIR . $filePath, '/path') extends APISpec {
        };
    }

    /** @test */
    public function throwsExceptionForInvalidOpenAPI(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage('OpenAPI could not be validated');

        new class(self::DIR . 'invalidOpenAPI.json', '/path') extends APISpec {
        };
    }

    /** @test */
    public function throwsExceptionIfNoPathMatches(): void
    {
        self::expectExceptionObject(new Exception('url does not match any paths defined by API'));

        new class(self::DIR . 'noReferences.json', 'incorrect/path') extends APISpec {
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
