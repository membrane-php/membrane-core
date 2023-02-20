<?php

declare(strict_types=1);

namespace OpenAPI\Processor;

use GuzzleHttp\Psr7\ServerRequest;
use Membrane\OpenAPI\Method;
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

    /**
     * @test
     * @dataProvider dataSetsToConvertToString
     */
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
            '1 empty Field' => [new Request('b', 'operationId', Method::GET, [new Field('')]),],
            '1 Field' => [new Request('c', 'operationId', Method::GET, [new Field('', new Passes())]),],
            '3 Fields' => [
                new Request(
                    'd',
                    'operationId',
                    Method::GET,
                    [new Field('a', new Passes()), new Field('b', new Fails()), new Field('c', new Passes())]
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToConvertToPHPString
     */
    public function toPHPTest(Request $sut): void
    {
        $actual = $sut->__toPHP();

        self::assertEquals($sut, eval('return ' . $actual . ';'));
    }

    /** @test */
    public function processesTest(): void
    {
        $sut = new Request('test', '', Method::GET, []);

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
                Result::invalid([
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
                    'header' => [],
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
                    'header' => [],
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
                Result::invalid([
                    'request' => ['method' => 'get', 'operationId' => ''],
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
        ];
    }

    /**
     * @test
     * @dataProvider dataSetsToProcess
     */
    public function processTest(mixed $value, array $processors, Result $expected): void
    {
        $sut = new Request('', '', Method::GET, $processors);

        $actual = $sut->process(new FieldName(''), $value);

        self::assertEquals($expected, $actual);
    }
}
