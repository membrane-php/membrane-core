<?php

declare(strict_types=1);

namespace OpenAPI\Specification;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\PathItem;
use cebe\openapi\spec\Response;
use Membrane\OpenAPI\Exception\CannotProcessRequest;
use Membrane\OpenAPI\Method;
use Membrane\OpenAPI\Specification\APISpec;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Membrane\OpenAPI\Specification\APISpec
 * @covers \Membrane\OpenAPI\Exception\CannotReadOpenAPI
 * @covers \Membrane\OpenAPI\Exception\CannotProcessOpenAPI
 * @covers \Membrane\OpenAPI\Exception\CannotProcessRequest
 * @uses   \Membrane\OpenAPI\PathMatcher
 * @uses   \Membrane\OpenAPI\Reader\OpenAPIFileReader
 */
class APISpecTest extends TestCase
{
    public const FIXTURES = __DIR__ . '/../../fixtures/OpenAPI/';

    /** @test */
    public function throwsExceptionIfNoPathMatches(): void
    {
        $fileName = 'noReferences.json';
        $url = 'incorrect/path';
        self::expectExceptionObject(CannotProcessRequest::pathNotFound($fileName, $url));

        new class(self::FIXTURES . $fileName, $url, Method::from('get')) extends APISpec {
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
        $class = new class(self::FIXTURES . $filePath, $url, $method) extends APISpec {
            public Operation $requestOperation;

            public function __construct(string $absoluteFilePath, string $url, Method $method)
            {
                parent::__construct($absoluteFilePath, $url, $method);
                $this->requestOperation = $this->getOperation($method);
            }
        };

        self::assertInstanceOf(PathItem::class, $class->pathItem);
        self::assertInstanceOf(Operation::class, $class->requestOperation);
        self::assertInstanceOf(Response::class, $class->pathItem->get->responses->getResponse('200'));
    }
}
