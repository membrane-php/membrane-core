<?php

declare(strict_types=1);

namespace Membrane\Tests\OpenAPI\Processor;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\UploadedFile;
use Membrane\Filter\String\JsonDecode;
use Membrane\OpenAPI\ContentType;
use Membrane\OpenAPI\Processor\Request;
use Membrane\OpenAPIReader\ValueObject\Valid\Enum\Method;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Request::class)]
#[UsesClass(Field::class)]
#[UsesClass(FieldName::class)]
#[UsesClass(MessageSet::class)]
#[UsesClass(Message::class)]
#[UsesClass(Result::class)]
#[UsesClass(Fails::class)]
#[UsesClass(Passes::class)]
#[UsesClass(ContentType::class)]
#[UsesClass(JsonDecode::class)]
class RequestTest extends TestCase
{
    public static function dataSetsToConvertToString(): array
    {
        return [
            'request with no processors' => [
                'Parse PSR-7 request',
                [],
            ],
            'request with processors inside' => [
                <<<END
                Parse PSR-7 request:
                \t"id":
                \t\t- will return valid.
                \t"id":
                \t\t- will return invalid.
                END,
                [new Field('id', new Passes()), new Field('id', new Fails())],
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToString')]
    #[Test]
    public function toStringTest(string $expected, array $processors): void
    {
        $sut = new Request('test', '', Method::GET, $processors);

        $actual = (string)$sut;

        self::assertSame($expected, $actual);
    }

    public static function dataSetsToConvertToPHPString(): array
    {
        return [
            'no chain' => [new Request('a', 'operationId', Method::GET, []),],
            '1 empty Field' => [new Request('b', 'operationId', Method::GET, ['path' => new Field('')]),],
            '1 Field' => [new Request('c', 'operationId', Method::GET, ['query' => new Field('', new Passes())]),],
            '3 Fields' => [
                new Request(
                    'd',
                    'operationId',
                    Method::GET,
                    [
                        'path' => new Field('a', new Passes()),
                        'query' => new Field('b', new Fails()),
                        'body' => new Field('c', new Passes()),
                    ]
                ),
            ],
        ];
    }

    #[DataProvider('dataSetsToConvertToPHPString')]
    #[Test]
    public function toPHPTest(Request $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    #[Test]
    public function processesTest(): void
    {
        $sut = new Request('test', '', Method::GET, []);

        self::assertEquals('test', $sut->processes());
    }

    #[Test]
    public function unsupportedValuesDoNotGetProcessed(): void
    {
        $expected = Result::invalid(
            5,
            new MessageSet(
                new FieldName(''),
                new Message('Request processor expects array or PSR7 HTTP request, %s passed', ['integer'])
            )
        );
        $observer = self::createMock(Processor::class);
        $observer->expects($this->never())
            ->method('process');
        $processors = [
            'path' => $observer,
            'query' => $observer,
            'header' => $observer,
            'cookie' => $observer,
            'body' => $observer,
        ];
        $sut = new Request('', '', Method::GET, $processors);

        $actual = $sut->process(new FieldName(''), 5);

        self::assertEquals($expected, $actual);
    }

    public static function dataSetsToProcess(): array
    {
        $validProcessor = new Field('', new Passes());
        $invalidProcessor = new Field('', new Fails());

        return [
            'array, no processors' => [
                [],
                [],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '',
                    'query' => '',
                    'header' => [],
                    'cookie' => [],
                    'body' => '',
                ]),
            ],
            'array, valid processors' => [
                [],
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '',
                    'query' => '',
                    'header' => [],
                    'cookie' => [],
                    'body' => '',
                ]),
            ],
            'array, valid and invalid processors' => [
                [],
                [
                    'path' => $validProcessor,
                    'query' => $invalidProcessor,
                    'header' => $validProcessor,
                    'cookie' => $invalidProcessor,
                    'body' => $validProcessor,
                ],
                Result::invalid(
                    [
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '',
                    'query' => '',
                    'header' => [],
                    'cookie' => [],
                    'body' => '',
                    ],
                    new MessageSet(new FieldName('', ''), new Message('I always fail', [])),
                    new MessageSet(new FieldName('', ''), new Message('I always fail', []))
                ),
            ],
            'guzzle server request, no processors' => [
                new ServerRequest('get', 'https://www.swaggerstore.io/pets?limit=5', [], 'request body'),
                [],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => ['Host' => ['www.swaggerstore.io']],
                    'cookie' => [],
                    'body' => 'request body',
                ]),
            ],
            'guzzle server request, valid processors' => [
                new ServerRequest('get', 'https://www.swaggerstore.io/pets?limit=5', [], 'request body'),
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => ['Host' => ['www.swaggerstore.io']],
                    'cookie' => [],
                    'body' => 'request body',
                ]),
            ],
            'guzzle server request, valid and invalid processors' => [
                new ServerRequest('get', 'https://www.swaggerstore.io/pets?limit=5', [], 'request body'),
                [
                    'path' => $validProcessor,
                    'query' => $invalidProcessor,
                    'header' => $validProcessor,
                    'cookie' => $invalidProcessor,
                    'body' => $validProcessor,
                ],
                Result::invalid(
                    [
                        'request' => ['method' => 'get', 'operationId' => ''],
                        'path' => '/pets',
                        'query' => 'limit=5',
                        'header' => ['Host' => ['www.swaggerstore.io']],
                        'cookie' => [],
                        'body' => 'request body',
                    ],
                    new MessageSet(new FieldName('', ''), new Message('I always fail', [])),
                    new MessageSet(new FieldName('', ''), new Message('I always fail', []))
                ),
            ],
            'guzzle server request, valid processors, invalid json' => [
                new ServerRequest(
                    'get',
                    'https://www.swaggerstore.io/pets?limit=5',
                    ['Content-Type' => 'application/json'],
                    '{"field": 2'
                ),
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::invalid(
                    [
                        'path' => '/pets',
                        'query' => 'limit=5',
                        'header' => [
                            'Host' => ['www.swaggerstore.io'],
                            'Content-Type' => ['application/json']
                        ],
                        'cookie' => [],
                        'body' => null,
                    ],
                    new MessageSet(
                        new FieldName('', ''),
                        new Message('Syntax error occurred', [])
                    )
                ),
            ],
            'guzzle server request, valid processors, json content type' => [
                new ServerRequest(
                    'get',
                    'https://www.swaggerstore.io/pets?limit=5',
                    ['Content-Type' => 'application/json'],
                    '{"field": 2}'
                ),
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [
                        'Host' => ['www.swaggerstore.io'],
                        'Content-Type' => ['application/json']
                    ],
                    'cookie' => [],
                    'body' => ['field' => 2],
                ]),
            ],
            'guzzle server request, valid processors, json content type, empty body' => [
                new ServerRequest(
                    'get',
                    'https://www.swaggerstore.io/pets?limit=5',
                    ['Content-Type' => 'application/json'],
                    ''
                ),
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [
                        'Host' => ['www.swaggerstore.io'],
                        'Content-Type' => ['application/json']
                    ],
                    'cookie' => [],
                    'body' => '',
                ]),
            ],
            'guzzle server request, valid processors, form type' => [
                (new ServerRequest(
                    'get',
                    'https://www.swaggerstore.io/pets?limit=5',
                    ['Content-Type' => 'application/x-www-form-urlencoded'],
                    null
                ))->withParsedBody(['field' => 3]),
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [
                        'Host' => ['www.swaggerstore.io'],
                        'Content-Type' => ['application/x-www-form-urlencoded']
                    ],
                    'cookie' => [],
                    'body' => ['field' => 3],
                ]),
            ],
            'guzzle server request, valid processors, with file uploads' => [
                (new ServerRequest(
                    'get',
                    'https://www.swaggerstore.io/pets?limit=5',
                    ['Content-Type' => 'multipart/x-www-form-urlencoded'],
                    null
                ))->withParsedBody(['field' => 3])
                    ->withUploadedFiles([
                        'file' => new UploadedFile(
                            new Stream(fopen('data://text/plain,filedata', 'r')),
                            null,
                            0
                        ),
                    ]),
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::valid([
                    'request' => ['method' => 'get', 'operationId' => ''],
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [
                        'Host' => ['www.swaggerstore.io'],
                        'Content-Type' => ['multipart/x-www-form-urlencoded']
                    ],
                    'cookie' => [],
                    'body' => ['field' => 3, 'file' => 'filedata'],
                ]),
            ],
        ];
    }

    #[DataProvider('dataSetsToProcess')]
    #[Test]
    public function processTest(mixed $value, array $processors, Result $expected): void
    {
        $sut = new Request('', '', Method::GET, $processors);

        $actual = $sut->process(new FieldName(''), $value);

        self::assertEquals($expected, $actual);
    }
}
