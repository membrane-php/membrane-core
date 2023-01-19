<?php

declare(strict_types=1);

namespace OpenAPI\Processor;

use GuzzleHttp\Psr7\ServerRequest;
use Membrane\OpenAPI\Processor\Request;
use Membrane\Processor;
use Membrane\Processor\Field;
use Membrane\Result\FieldName;
use Membrane\Result\Message;
use Membrane\Result\MessageSet;
use Membrane\Result\Result;
use Membrane\Validator\Utility\Fails;
use Membrane\Validator\Utility\Passes;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @covers \Membrane\OpenAPI\Processor\Request
 * @uses   \Membrane\Processor\Field
 * @uses   \Membrane\Result\FieldName
 * @uses   \Membrane\Result\MessageSet
 * @uses   \Membrane\Result\Message
 * @uses   \Membrane\Result\Result
 * @uses   \Membrane\Validator\Utility\Fails
 * @uses   \Membrane\Validator\Utility\Passes
 */
class RequestTest extends TestCase
{
    /** @test */
    public function processesTest(): void
    {
        $sut = new Request('test', []);

        self::assertEquals('test', $sut->processes());
    }

    /** @test */
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
        $sut = new Request('', $processors);

        $actual = $sut->process(new FieldName(''), 5);

        self::assertEquals($expected, $actual);
    }

    public function dataSetsToProcess(): array
    {
        $validProcessor = new Field('', new Passes());

        $invalidProcessor = new Field('', new Fails());

        $uri = self::createMock(UriInterface::class);
        $uri->method('getPath')
            ->willReturn('/pets');
        $uri->method('getQuery')
            ->willReturn('limit=5');

        $serverRequest = self::createMock(ServerRequestInterface::class);
        $serverRequest->method('getUri')
            ->willReturn($uri);
        $serverRequest->method('getBody')
            ->willReturn('request body');


        return [
            'array, no processors' => [
                [],
                [],
                Result::valid([
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
                Result::invalid([
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
            'mock server request, no processors' => [
                $serverRequest,
                [],
                Result::valid([
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [],
                    'cookie' => [],
                    'body' => 'request body',
                ]),
            ],
            'mock server request, valid processors' => [
                $serverRequest,
                [
                    'path' => $validProcessor,
                    'query' => $validProcessor,
                    'header' => $validProcessor,
                    'cookie' => $validProcessor,
                    'body' => $validProcessor,
                ],
                Result::valid([
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [],
                    'cookie' => [],
                    'body' => 'request body',
                ]),
            ],
            'mock server request, valid and invalid processors' => [
                $serverRequest,
                [
                    'path' => $validProcessor,
                    'query' => $invalidProcessor,
                    'header' => $validProcessor,
                    'cookie' => $invalidProcessor,
                    'body' => $validProcessor,
                ],
                Result::invalid([
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [],
                    'cookie' => [],
                    'body' => 'request body',
                ],
                    new MessageSet(new FieldName('', ''), new Message('I always fail', [])),
                    new MessageSet(new FieldName('', ''), new Message('I always fail', []))
                ),
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
                    'path' => '/pets',
                    'query' => 'limit=5',
                    'header' => [],
                    'cookie' => [],
                    'body' => 'request body',
                ]),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToProcess
     */
    public function processTest(mixed $value, array $processors, Result $expected): void
    {
        $sut = new Request('', $processors);

        $actual = $sut->process(new FieldName(''), $value);

        self::assertEquals($expected, $actual);
    }
}
